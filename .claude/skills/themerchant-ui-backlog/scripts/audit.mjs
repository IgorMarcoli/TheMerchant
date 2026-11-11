#!/usr/bin/env node
// Audita a interface do TheMerchant e regenera o BACKLOG.md da skill.
// O status de cada item vem de uma verificação no código, nunca de marcação manual.
//
//   node audit.mjs           regenera BACKLOG.md e status.json e imprime o resumo
//   node audit.mjs --check   só imprime o resumo, sem gravar nada
//   node audit.mjs --hook    modo hook do Claude Code: lê o JSON do stdin e só roda
//                            quando o arquivo editado afeta a interface
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const SKILL_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const ROOT = path.resolve(SKILL_DIR, '..', '..', '..');
const BACKLOG = path.join(SKILL_DIR, 'BACKLOG.md');
const STATE = path.join(SKILL_DIR, 'status.json');
const RELEVANT = /(^|[\\/])(resources[\\/](views|css|js)|public[\\/](css|js)|routes[\\/]web\.php|app[\\/](Providers|View)|tailwind\.config|vite\.config|\.claude[\\/]skills[\\/]themerchant-ui-backlog[\\/]scripts)/;

// ---------- leitura do projeto ----------

const cache = new Map();

function read(rel) {
    if (!cache.has(rel)) {
        try {
            cache.set(rel, fs.readFileSync(path.join(ROOT, rel), 'utf8').replace(/\r\n/g, '\n'));
        } catch {
            cache.set(rel, null);
        }
    }
    return cache.get(rel);
}

function walk(rel) {
    const abs = path.join(ROOT, rel);
    if (!fs.existsSync(abs)) return [];
    return fs.readdirSync(abs, { withFileTypes: true }).flatMap(entry => {
        const child = `${rel}/${entry.name}`;
        return entry.isDirectory() ? walk(child) : [child];
    });
}

const VIEWS = walk('resources/views').filter(file => file.endsWith('.blade.php'));
const CSS = ['public/css/theme.css', 'public/css/storefront.css', 'resources/css/app.css'];
const LAYOUT = 'resources/views/layouts/app.blade.php';
const HEADER = 'resources/views/components/storefront-header.blade.php';
const view = name => `resources/views/${name}.blade.php`;
const lineAt = (text, index) => text.slice(0, index).split('\n').length;

// "->" e "=>" de Blade/Alpine quebram a leitura de tags; trocamos por caracteres do mesmo tamanho.
const masked = text => text.replace(/->/g, '-~').replace(/=>/g, '=~');
// Comentários (HTML, Blade, CSS) viram espaços, preservando as quebras de linha e a numeração.
const uncommented = text => text.replace(/<!--[\s\S]*?-->|\{\{--[\s\S]*?--\}\}|\/\*[\s\S]*?\*\//g, c => c.replace(/[^\n]/g, ' '));
const tagPattern = names => new RegExp(`<(${names})\\b((?:[^>"']|"[^"]*"|'[^']*')*)>`, 'g');
const attr = (attrs, name) => (attrs.match(new RegExp(`(?:^|\\s)${name}="([^"]*)"`)) || [])[1];

// Procura um padrão nos arquivos e devolve "arquivo:linha" de cada ocorrência aceita.
function find(files, regex, keep = () => true, { mask = false } = {}) {
    const hits = [];
    for (const file of files) {
        const raw = read(file);
        if (raw == null) continue;
        const text = mask ? masked(uncommented(raw)) : uncommented(raw);
        const re = new RegExp(regex.source, regex.flags.includes('g') ? regex.flags : `${regex.flags}g`);
        for (const match of text.matchAll(re)) {
            if (keep(match, text, file)) hits.push(`${file}:${lineAt(text, match.index)}`);
        }
    }
    return hits;
}

// Linhas de Blade/HTML com conteúdo, sem comentários.
function codeLines(file) {
    const text = read(file);
    if (text == null) return [];
    return text.split('\n').map((line, i, all) => ({ file, n: i + 1, line, prev: all[i - 1] || '' }))
        .filter(({ line }) => !/^\s*(<!--|\{\{--)/.test(line));
}

const EMOJI = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{23F3}\u{2B50}\u{2139}]/u;
const TYPOGRAPHIC = /[★☆✓]/gu; // ★ ☆ ✓ são caracteres de texto, não ícones

// ---------- resultado das verificações ----------

const feito = (resumo = '') => ({ status: 'feito', resumo, onde: [] });
const pendente = (onde, resumo) => ({ status: 'pendente', resumo, onde });
const parcial = (onde, resumo) => ({ status: 'parcial', resumo, onde });
const plural = (n, um, varios) => `${n} ${n === 1 ? um : varios}`;
const byHits = (hits, um = 'ocorrência', varios = 'ocorrências') =>
    hits.length ? pendente(hits, plural(hits.length, um, varios)) : feito();
const contains = (file, needle) => (read(file) || '').includes(needle);
const lineOf = (file, needle) => {
    const text = read(file) || '';
    const index = typeof needle === 'string' ? text.indexOf(needle) : text.search(needle);
    return index < 0 ? `${file}` : `${file}:${lineAt(text, index)}`;
};

// ---------- catálogo: tudo o que a análise de UI/UX apontou ----------
// Para acrescentar um item: dê um id novo, explique o porquê e escreva um check que leia o código.

const ITEMS = [
    // ===== Bugs e conteúdo =====
    {
        id: 'BUG-01', area: 'Bugs e conteúdo', prioridade: 'Crítica', regra: 'consistency',
        titulo: 'Texto de componente sem escape duplo (ex.: "Mensagens &amp; Negociações")',
        porque: 'Atributos estáticos de componentes Blade já são escapados no {{ }}; escrever &amp; no atributo aparece literalmente na tela.',
        como: 'Escreva o caractere direto no atributo (title="Mensagens & Negociações").',
        check: () => byHits(find(VIEWS, /<x-[\w.-]+[^>]*\b(?:title|eyebrow|description|action)="[^"]*&amp;/)),
    },
    {
        id: 'BUG-02', area: 'Bugs e conteúdo', prioridade: 'Crítica', regra: 'trust',
        titulo: 'Nota do vendedor sem valor inventado',
        porque: 'Um fallback como ?? \'5.00\' mostra 5 estrelas para quem nunca foi avaliado e engana o comprador.',
        como: 'Mostre "Sem avaliações" quando total_reviews for 0 e formate a nota como 4,75/5.',
        check: () => byHits(find(VIEWS, /reputation_score\s*\?\?\s*'[\d.,]+'/)),
    },
    {
        id: 'BUG-03', area: 'Bugs e conteúdo', prioridade: 'Crítica', regra: 'empty-states',
        titulo: 'Anúncio sem foto com arte e aviso, não uma caixa vazia',
        porque: 'Sem fotos, o detalhe mostrava uma área escura de 440px com um ícone de 21px.',
        como: 'Use a classe .tm-detail-art no contêiner da galeria vazia e um texto explicando que não há fotos.',
        check: () => contains(view('listings/show'), 'tm-detail-art') ? feito() : pendente([view('listings/show')], 'galeria vazia sem .tm-detail-art'),
    },
    {
        id: 'BUG-04', area: 'Bugs e conteúdo', prioridade: 'Crítica', regra: 'alt-text',
        titulo: 'Capas dos jogos com imagem reserva e alt decorativo',
        porque: 'Se a URL do banco falha, o card mostrava o texto alternativo quebrado.',
        como: 'Use onerror com data-fallback para a imagem local e depois o monograma; alt="" porque o nome já aparece no card.',
        check: () => contains(view('home'), 'data-fallback') && contains(view('home'), 'onerror')
            ? feito() : pendente([lineOf(view('home'), 'tm-game-card')], 'card sem fallback de imagem'),
    },
    {
        id: 'BUG-05', area: 'Bugs e conteúdo', prioridade: 'Crítica', regra: 'consistency',
        titulo: 'Páginas de erro 404, 403 e 419 no visual do marketplace',
        porque: 'A página padrão do Laravel ("Not Found") quebra a identidade e não oferece caminho de volta.',
        como: 'Crie resources/views/errors/{codigo}.blade.php estendendo errors.layout.',
        check: () => {
            const missing = ['404', '403', '419'].filter(code => read(view(`errors/${code}`)) == null);
            return missing.length ? pendente(missing.map(code => view(`errors/${code}`)), `faltam ${missing.join(', ')}`) : feito();
        },
    },
    {
        id: 'BUG-06', area: 'Bugs e conteúdo', prioridade: 'Alta', regra: 'consistency',
        titulo: 'Páginas 500 e 503 próprias, sem depender do layout',
        porque: 'Se o erro vier do próprio layout (cabeçalho, sessão, banco), uma página 500 que o estende também falha.',
        como: 'Crie errors/500.blade.php e errors/503.blade.php como HTML autônomo (estilo inline, sem @extends).',
        check: () => {
            const bad = ['500', '503'].filter(code => {
                const text = read(view(`errors/${code}`));
                return text == null || text.includes("@extends('layouts.app')") || text.includes("@extends('errors.layout'");
            });
            return bad.length ? pendente(bad.map(code => view(`errors/${code}`)), `faltam ${bad.join(', ')} autônomas`) : feito();
        },
    },
    {
        id: 'BUG-07', area: 'Bugs e conteúdo', prioridade: 'Alta', regra: 'consistency',
        titulo: '404 de endereço inexistente mostra o usuário logado',
        porque: 'Sem rota correspondente o Laravel não inicia a sessão, e o cabeçalho aparece como se o usuário tivesse saído.',
        como: 'Adicione Route::fallback(fn () => abort(404)) no fim de routes/web.php (grupo web).',
        check: () => contains('routes/web.php', 'Route::fallback') ? feito() : pendente(['routes/web.php'], 'sem Route::fallback'),
    },
    {
        id: 'BUG-08', area: 'Bugs e conteúdo', prioridade: 'Crítica', regra: 'consistency',
        titulo: 'Paginação escura em todas as listagens',
        porque: 'A view padrão do Laravel usa fundo branco e destoa do tema escuro.',
        como: 'Paginator::defaultView(\'vendor.pagination.catalog\') no AppServiceProvider.',
        check: () => contains('app/Providers/AppServiceProvider.php', 'Paginator::defaultView')
            ? feito() : pendente(['app/Providers/AppServiceProvider.php'], 'sem defaultView'),
    },
    {
        id: 'BUG-09', area: 'Bugs e conteúdo', prioridade: 'Alta', regra: 'consistency',
        titulo: 'Status com rótulos em português, não o valor cru do banco',
        porque: 'Valores como em_andamento ou aguardando_pagamento aparecem com sublinhado e em caixa alta; cada tela formata de um jeito.',
        como: 'Crie um componente <x-status-badge :status="..." /> com um mapa de rótulos e cores e use-o em pedidos, vendas e admin.',
        check: () => byHits([
            ...find(VIEWS, /str_replace\(\s*(?:\[[^\]]*'_'|'_')/),
            ...find(VIEWS, /\{\{\s*\$\w+(?:->\w+)*->(?:status|delivery_status)\s*\}\}/),
        ]),
    },
    {
        id: 'BUG-10', area: 'Bugs e conteúdo', prioridade: 'Alta', regra: 'consistency',
        titulo: 'Linha do tempo do pedido: etapa 1 não fica "Pendente" depois de pago',
        porque: 'O subtítulo da etapa "Pedido Criado" é fixo e contradiz o pagamento confirmado.',
        como: 'Troque o texto fixo por um condicional ("Registrado" quando houver pagamento).',
        check: () => {
            const file = view('orders/show');
            const match = (read(file) || '').match(/1\. Pedido Criado[\s\S]{0,300}?>Pendente<\/span>/);
            return match ? pendente([lineOf(file, '1. Pedido Criado')], 'texto fixo "Pendente"') : feito();
        },
    },

    // ===== Acessibilidade =====
    {
        id: 'A11Y-01', area: 'Acessibilidade', prioridade: 'Crítica', regra: 'form-labels',
        titulo: 'Todo <label> associado ao seu campo',
        porque: 'Sem for/id o leitor de tela não anuncia o rótulo e clicar no texto não foca o campo.',
        como: 'Dê id ao campo e for ao label, ou envolva o campo dentro do label.',
        check: () => byHits(find(VIEWS, /<label\b((?:[^>"']|"[^"]*"|'[^']*')*)>([\s\S]*?)<\/label>/,
            m => !/\bfor="/.test(m[1]) && !/<(input|select|textarea)\b/.test(m[2]), { mask: true }), 'rótulo solto', 'rótulos soltos'),
    },
    {
        id: 'A11Y-02', area: 'Acessibilidade', prioridade: 'Crítica', regra: 'form-labels',
        titulo: 'Todo campo com nome acessível',
        porque: 'Campo sem label, aria-label ou label envolvente é anunciado só como "caixa de edição".',
        como: 'Associe um label visível; use aria-label só quando o contexto visual já deixa claro o que é.',
        check: () => byHits(find(VIEWS, tagPattern('input|select|textarea'), (m, text) => {
            const attrs = m[2];
            if (m[1] === 'input' && /^(hidden|submit|button|reset|image)$/.test(attr(attrs, 'type') || '')) return false;
            if (/(?:^|\s)(?::|x-bind:)?aria-label(?:ledby)?="/.test(attrs)) return false;
            const id = attr(attrs, 'id');
            if (id && text.includes(`for="${id}"`)) return false;
            return text.lastIndexOf('<label', m.index) <= text.lastIndexOf('</label>', m.index);
        }, { mask: true }), 'campo sem nome', 'campos sem nome'),
    },
    {
        id: 'A11Y-03', area: 'Acessibilidade', prioridade: 'Crítica', regra: 'error-placement',
        titulo: 'Erros de validação junto ao campo e ligados por aria-describedby',
        porque: 'Mensagem só no topo, ou sem id, não é lida quando o usuário volta ao campo inválido.',
        como: 'Use <x-field-error name="campo" id="campo-error" /> e no input @error(\'campo\') aria-invalid="true" aria-describedby="campo-error" @enderror.',
        check: () => byHits([
            ...find(VIEWS, /@error\([^)]*\)\s*<(p|span|div)\b(?![^>]*\bid=)/),
            ...VIEWS.filter(file => contains(file, '$errors->first()') && /<(textarea|select)\b|<input\b(?![^>]*type="hidden")/.test(read(file)) && !contains(file, '<x-field-error'))
                .map(file => lineOf(file, '$errors->first()')),
        ]),
    },
    {
        id: 'A11Y-04', area: 'Acessibilidade', prioridade: 'Crítica', regra: 'focus-management',
        titulo: 'Modais prendem e devolvem o foco',
        porque: 'Sem isso o Tab sai do modal para a página escondida atrás e o foco se perde ao fechar.',
        como: 'Adicione x-trap.inert.noscroll="aberto" no elemento role="dialog" (plugin Focus do Alpine, já carregado no layout).',
        check: () => byHits(find(VIEWS, /role="dialog"/, (m, text) => !text.slice(Math.max(0, m.index - 500), m.index + 300).includes('x-trap')), 'modal', 'modais'),
    },
    {
        id: 'A11Y-05', area: 'Acessibilidade', prioridade: 'Crítica', regra: 'aria-labels',
        titulo: 'Emojis decorativos ocultos do leitor de tela',
        porque: 'O leitor lê o nome do emoji ("cronômetro", "alfinete") no meio do conteúdo.',
        como: 'Envolva em <span aria-hidden="true">; se o emoji indica um estado, acrescente um <span class="sr-only"> com o texto.',
        check: () => byHits(VIEWS.flatMap(codeLines)
            .filter(({ line, prev }) => EMOJI.test(line) && !line.includes('aria-hidden="true"') && !prev.includes('aria-hidden="true"'))
            .map(({ file, n }) => `${file}:${n}`), 'emoji exposto', 'emojis expostos'),
    },
    {
        id: 'A11Y-06', area: 'Acessibilidade', prioridade: 'Alta', regra: 'touch-target-size',
        titulo: 'Áreas de toque com pelo menos 44×44px',
        porque: 'O padrão do projeto (docs/PADRAO_VISUAL.md) pede 44px; botões de 40px e links de voltar de 16px de altura são difíceis de tocar.',
        como: 'Use min-h-11 min-w-11 (ou p-3) nos botões de ícone e nos links "← Voltar".',
        check: () => byHits([
            ...find(VIEWS, tagPattern('button|a'), m => {
                const cls = attr(m[2], 'class') || '';
                return /\bw-(?:6|7|8|9|10)\b/.test(cls) && /\bh-(?:6|7|8|9|10)\b/.test(cls) && !/\bmin-w-(?:11|12)\b/.test(cls);
            }, { mask: true }),
            ...find(VIEWS, /<a\b((?:[^>"']|"[^"]*"|'[^']*')*)>\s*(?:&larr;|←)/, m => {
                const cls = attr(m[1], 'class') || '';
                return !/\bmin-h-(?:11|12)\b|\bpy-(?:3|4)\b|\bp-3\b/.test(cls);
            }, { mask: true }),
        ], 'alvo pequeno', 'alvos pequenos'),
    },
    {
        id: 'A11Y-07', area: 'Acessibilidade', prioridade: 'Alta', regra: 'loading-buttons',
        titulo: 'Envios críticos mostram carregamento e bloqueiam clique duplo',
        porque: '"Confirmar pedido e pagar" e "Adicionar ao carrinho" não dão retorno ao clicar e aceitam um segundo clique.',
        como: 'Envolva o form em x-data="{ enviando: false }", use @submit="enviando = true", :disabled="enviando", :aria-busy e troque o texto do botão.',
        check: () => {
            const missing = [];
            if (!/:disabled=|aria-busy/.test(read(view('checkout/index')) || '')) missing.push(lineOf(view('checkout/index'), 'type="submit"'));
            const show = read(view('listings/show')) || '';
            const cartForm = show.slice(show.indexOf("route('cart.add'"), show.indexOf('</form>', show.indexOf("route('cart.add'")));
            if (show.includes("route('cart.add'") && !/:disabled=|aria-busy/.test(cartForm)) missing.push(lineOf(view('listings/show'), "route('cart.add'"));
            return byHits(missing, 'formulário', 'formulários');
        },
    },
    {
        id: 'A11Y-08', area: 'Acessibilidade', prioridade: 'Alta', regra: 'readable-font-size',
        titulo: 'Campos de formulário com 16px no celular',
        porque: 'Abaixo de 16px o iPhone dá zoom ao tocar no campo. A regra base está em 14px e vários campos usam text-xs (12px).',
        como: 'Em public/css/storefront.css, dentro de @media (max-width:767px), force font-size:16px !important em input, select e textarea do .tm-main.',
        check: () => {
            const fixed = CSS.some(file => /[^{}]*\binput\b[^{}]*\{[^}]*font-size:\s*(?:16px|1rem)\s*!important/.test(read(file) || ''));
            if (fixed) return feito();
            const small = find(VIEWS, tagPattern('input|select|textarea'), m => /\btext-(?:xs|sm|\[\d+px\])\b/.test(attr(m[2], 'class') || '') && !/type="(hidden|checkbox|radio)"/.test(m[2]), { mask: true });
            return pendente([lineOf('public/css/storefront.css', 'font-size:14px; }'), ...small], plural(small.length + 1, 'ponto', 'pontos'));
        },
    },
    {
        id: 'A11Y-09', area: 'Acessibilidade', prioridade: 'Média', regra: 'font-scale',
        titulo: 'Nenhum texto abaixo de 12px',
        porque: 'Home e catálogo têm dezenas de textos de 7 a 11px (rótulos de preço, tags, eyebrows), difíceis de ler no celular.',
        como: 'Defina a escala 12/14/16/18/24/32: legendas e eyebrows com 12px no mínimo; troque text-[9px]/[10px]/[11px] por text-xs.',
        check: () => byHits([
            ...find(VIEWS, /text-\[(?:[0-9]|1[01])(?:\.\d+)?px\]/),
            ...find(CSS, /font(?:-size)?:\s*(?:[0-9]|1[01])(?:\.\d+)?px/),
        ]),
    },
    {
        id: 'A11Y-10', area: 'Acessibilidade', prioridade: 'Média', regra: 'color-contrast',
        titulo: 'Texto com contraste mínimo de 4,5:1',
        porque: 'slate-600 sobre o fundo escuro dá 3,4:1 (ex.: separador do rodapé); o resto da paleta passa.',
        como: 'Use slate-400 ou slate-500 para texto secundário; reserve 600/700 para bordas.',
        check: () => byHits([
            ...find(VIEWS, /\btext-slate-(?:600|700)\b/),
            ...find(CSS, /\bcolor:\s*rgb\(var\(--tm-neutral-(?:600|700)\)\)/),
        ]),
    },
    {
        id: 'A11Y-11', area: 'Acessibilidade', prioridade: 'Média', regra: 'keyboard-nav',
        titulo: 'Links que abrem nova aba avisam o usuário',
        porque: 'target="_blank" sem aviso tira o usuário do contexto sem explicação.',
        como: 'Acrescente <span class="sr-only">(abre em nova aba)</span> e um ícone de link externo, ou abra na mesma aba.',
        check: () => byHits(find(VIEWS, /<a\b[^>]*target="_blank"/, (m, text) => {
            const end = text.indexOf('</a>', m.index);
            return !text.slice(m.index, end).includes('nova aba');
        }, { mask: true }), 'link', 'links'),
    },
    {
        id: 'A11Y-12', area: 'Acessibilidade', prioridade: 'Média', regra: 'confirmation-dialogs',
        titulo: 'Confirmações em modal acessível, não confirm() do navegador',
        porque: 'O confirm() nativo destoa do visual e já existe um modal estilizado (esvaziar carrinho) como modelo.',
        como: 'Reaproveite o padrão do modal do carrinho (x-data + x-trap) para arquivar anúncio e excluir categoria.',
        check: () => byHits(find(VIEWS, /\bconfirm\(/)),
    },
    {
        id: 'A11Y-13', area: 'Acessibilidade', prioridade: 'Média', regra: 'heading-hierarchy',
        titulo: 'Perfil começa pelo título da página',
        porque: 'O aviso de e-mail e o link de vendedor aparecem soltos antes do <h1>, sem hierarquia.',
        como: 'Mova o aviso de e-mail e o link "Gerenciar meu perfil de vendedor" para dentro do conteúdo, depois do x-page-heading.',
        check: () => {
            const file = view('profile/edit');
            const text = read(file) || '';
            const heading = text.indexOf('<x-page-heading');
            const before = ['E-mail confirmado', 'Gerenciar meu perfil de vendedor'].filter(t => text.includes(t) && text.indexOf(t) < heading);
            return before.length ? pendente(before.map(t => lineOf(file, t)), plural(before.length, 'bloco antes do h1', 'blocos antes do h1')) : feito();
        },
    },

    // ===== Desempenho e build =====
    {
        id: 'PERF-01', area: 'Desempenho e build', prioridade: 'Alta', regra: 'third-party-scripts',
        titulo: 'Tailwind compilado pelo Vite, sem cdn.tailwindcss.com',
        porque: 'O CDN compila o CSS no navegador a cada carga (aviso no console: não é para produção) e causa pulo de layout.',
        como: 'Use @vite([\'resources/css/app.css\', \'resources/js/app.js\']) no layout, mova os tokens para tailwind.config.js (já existe) e remova o script do CDN.',
        check: () => {
            const text = read(LAYOUT) || '';
            const cdn = text.includes('cdn.tailwindcss.com');
            const vite = text.includes('@vite(');
            if (!cdn && vite) return feito();
            return (vite ? parcial : pendente)([lineOf(LAYOUT, cdn ? 'cdn.tailwindcss.com' : '<head>')], vite ? '@vite presente, mas o CDN continua' : 'layout ainda usa o CDN');
        },
    },
    {
        id: 'PERF-02', area: 'Desempenho e build', prioridade: 'Alta', regra: 'third-party-scripts',
        titulo: 'Alpine com versão fixa (ou empacotado pelo Vite)',
        porque: '@3.x.x pega sempre a última versão: uma atualização pode mudar o comportamento sem nenhum commit.',
        como: 'Ao migrar para o Vite, inicie o Alpine (e o plugin Focus) em resources/js/app.js e remova os CDNs; antes disso, fixe a versão exata nas URLs.',
        check: () => byHits(find([LAYOUT], /@\d+\.x(?:\.x)?\//), 'script sem versão', 'scripts sem versão'),
    },
    {
        id: 'PERF-03', area: 'Desempenho e build', prioridade: 'Média', regra: 'color-semantic',
        titulo: 'Configuração de cores em um só lugar',
        porque: 'O layout repete a configuração do tailwind.config.js inline; mudar uma não muda a outra.',
        como: 'Resolvido junto com PERF-01: apague o bloco tailwind.config = {...} do layout.',
        check: () => contains(LAYOUT, 'tailwind.config =') ? pendente([lineOf(LAYOUT, 'tailwind.config =')], 'config duplicada no layout') : feito(),
    },
    {
        id: 'PERF-04', area: 'Desempenho e build', prioridade: 'Média', regra: 'main-thread-budget',
        titulo: 'Contador de mensagens não lidas fora da view do cabeçalho',
        porque: 'O cabeçalho chama o ChatService direto da view em toda página autenticada, sem cache.',
        como: 'Crie um View Composer (app/View/Composers) que compartilhe $unreadChatCount com cache curto.',
        check: () => contains(HEADER, 'ChatService::class') ? pendente([lineOf(HEADER, 'ChatService::class')], 'consulta na view') : feito(),
    },
    {
        id: 'PERF-05', area: 'Desempenho e build', prioridade: 'Média', regra: 'image-optimization',
        titulo: 'Imagens com dimensões e carregamento preguiçoso',
        porque: 'Imagem sem width/height causa pulo de layout; fora da primeira tela deve usar loading="lazy".',
        como: 'Informe width e height e use loading="lazy" (ou fetchpriority="high" na imagem principal).',
        check: () => byHits(find(VIEWS, tagPattern('img'), m => {
            const a = m[2];
            const sized = /(?:^|\s):?width="/.test(a) && /(?:^|\s):?height="/.test(a);
            const loading = /loading="|fetchpriority="/.test(a);
            return !sized || !loading;
        }, { mask: true }), 'imagem', 'imagens'),
    },

    // ===== Layout e navegação =====
    {
        id: 'NAV-01', area: 'Layout e navegação', prioridade: 'Alta', regra: 'nav-hierarchy',
        titulo: 'Cabeçalho compacto no celular',
        porque: 'Logado, o cabeçalho ocupa ~345 de 812px (42% da primeira tela) com quatro faixas de navegação.',
        como: 'No celular, recolha a subnavegação e a área do usuário num menu com botão (aria-expanded) ou numa faixa com rolagem horizontal.',
        check: () => contains(HEADER, 'aria-expanded') ? feito() : pendente([lineOf(HEADER, 'tm-subnav')], 'navegação sempre aberta'),
    },
    {
        id: 'NAV-02', area: 'Layout e navegação', prioridade: 'Alta', regra: 'consistency',
        titulo: 'Uma busca só na página do catálogo',
        porque: 'O catálogo mostra a busca do cabeçalho e a do filtro ao mesmo tempo, com o mesmo nome de campo.',
        como: 'Esconda a busca do cabeçalho em listings.index (@unless(request()->routeIs(\'listings.index\'))) ou remova a do filtro.',
        check: () => {
            const header = read(HEADER) || '';
            const search = header.indexOf('tm-search');
            const conditional = header.slice(Math.max(0, search - 300), search).includes("routeIs('listings.index')");
            return !contains(view('listings/index'), 'name="busca"') || conditional ? feito() : pendente([lineOf(view('listings/index'), 'name="busca"')], 'duas buscas na tela');
        },
    },
    {
        id: 'NAV-03', area: 'Layout e navegação', prioridade: 'Alta', regra: 'mobile-first',
        titulo: 'Catálogo com 2 colunas no celular, como a home',
        porque: 'Com 1 coluna o primeiro anúncio só aparece depois de mais de uma tela de rolagem.',
        como: 'Troque grid-cols-1 por grid-cols-2 na grade de resultados.',
        check: () => {
            const file = view('listings/partials/results');
            return /\bgrid grid-cols-2\b/.test(read(file) || '') ? feito() : pendente([lineOf(file, 'grid grid-cols-1')], 'grade com 1 coluna');
        },
    },
    {
        id: 'NAV-04', area: 'Layout e navegação', prioridade: 'Média', regra: 'consistency',
        titulo: 'Nota do vendedor dentro do card do anúncio',
        porque: 'No catálogo a nota fica solta embaixo do card, fora da área clicável e do visual da home.',
        como: 'Passe a reputação para o componente storefront-listing e remova o parágrafo do partial.',
        check: () => contains(view('listings/partials/results'), 'reputation_score')
            ? pendente([lineOf(view('listings/partials/results'), 'reputation_score')], 'nota fora do card') : feito(),
    },
    {
        id: 'NAV-05', area: 'Layout e navegação', prioridade: 'Média', regra: 'consistency',
        titulo: 'Contêiner das páginas sem padding duplicado',
        porque: 'O .tm-main já tem padding; páginas que somam px-/py- no contêiner ficam com margens diferentes das outras.',
        como: 'Use só a largura (mx-auto max-w-*) no contêiner da página e deixe o padding com o .tm-main.',
        check: () => byHits(find(VIEWS.filter(f => !f.includes('/components/')), /class="([^"]*\bmx-auto\b[^"]*\bpx-\d[^"]*)"/,
            m => !/\b(?:bg|border)-/.test(m[1])), 'página', 'páginas'),
    },
    {
        id: 'NAV-06', area: 'Layout e navegação', prioridade: 'Média', regra: 'nav-hierarchy',
        titulo: 'Rodapé de marketing fora da área administrativa',
        porque: 'O banner "Seu próximo upgrade está aqui" aparece nas telas de admin, que não são de venda.',
        como: 'No marketplace-footer, mostre o banner só quando não for request()->routeIs(\'admin.*\').',
        check: () => contains('resources/views/components/marketplace-footer.blade.php', "routeIs('admin.")
            ? feito() : pendente([lineOf('resources/views/components/marketplace-footer.blade.php', 'market-footer-banner')], 'banner em todas as telas'),
    },
    {
        id: 'NAV-07', area: 'Layout e navegação', prioridade: 'Média', regra: 'progressive-disclosure',
        titulo: 'Checkout mostra em que etapa a compra está',
        porque: 'Carrinho → revisão → pagamento não tem indicação de progresso.',
        como: 'Adicione um indicador de etapas (lista com aria-current="step") no topo do checkout e do carrinho.',
        check: () => contains(view('checkout/index'), 'aria-current="step"') ? feito() : pendente([view('checkout/index')], 'sem indicador de etapas'),
    },
    {
        id: 'NAV-08', area: 'Layout e navegação', prioridade: 'Média', regra: 'destructive-nav-separation',
        titulo: '"Sair" como ação secundária no cabeçalho',
        porque: 'O botão verde principal do cabeçalho é o de sair, enquanto "Minha conta" fica em segundo plano.',
        como: 'Deixe "Sair" como link discreto (ou dentro do menu da conta) e não use .tm-button nele.',
        check: () => /route\('logout'\)[\s\S]{0,200}?tm-button\b/.test(read(HEADER) || '') ? pendente([lineOf(HEADER, "route('logout')")], 'logout com estilo primário') : feito(),
    },
    {
        id: 'NAV-09', area: 'Layout e navegação', prioridade: 'Média', regra: 'input-type-keyboard',
        titulo: 'Enter no chat não envia mensagem em celular',
        porque: 'No teclado do celular, Enter é usado para quebrar linha; enviar no Enter manda mensagens pela metade.',
        como: 'Envie no Enter só quando matchMedia(\'(pointer: fine)\').matches; no toque, use o botão Enviar.',
        check: () => {
            const text = read(view('chat/index')) || '';
            return !text.includes('@keydown.enter') || /pointer:\s*(?:coarse|fine)|matchMedia/.test(text) ? feito() : pendente([lineOf(view('chat/index'), '@keydown.enter')], 'Enter sempre envia');
        },
    },

    // ===== Visual e consistência =====
    {
        id: 'UI-01', area: 'Visual e consistência', prioridade: 'Alta', regra: 'no-emoji-icons',
        titulo: 'Ícones SVG (x-icon) no lugar de emojis',
        porque: 'Emojis mudam de desenho conforme o sistema e não seguem o estilo Lucide do resto do site.',
        como: 'Acrescente os ícones que faltam em components/icon.blade.php (Lucide) e troque cada emoji por <x-icon name="..." />.',
        check: () => byHits(VIEWS.flatMap(codeLines)
            .filter(({ line }) => EMOJI.test(line.replace(TYPOGRAPHIC, '')))
            .map(({ file, n }) => `${file}:${n}`), 'emoji', 'emojis'),
    },
    {
        id: 'UI-02', area: 'Visual e consistência', prioridade: 'Média', regra: 'color-semantic',
        titulo: 'Escala de verdes (brand) em ordem, do mais claro ao mais escuro',
        porque: 'brand-300 é igual a brand-500 e brand-400 igual a brand-600; por isso o hover clareia em uma tela e escurece em outra.',
        como: 'Gere uma escala 50→950 com luminância decrescente em public/css/theme.css e revise os pares bg/hover.',
        check: () => {
            const file = 'public/css/theme.css';
            const text = read(file) || '';
            const shades = [...text.matchAll(/--tm-brand-(\d+):\s*(\d+)\s+(\d+)\s+(\d+);/g)].map(m => ({
                shade: m[1], index: m.index, lum: [m[2], m[3], m[4]].map(Number).reduce((sum, v, i) => sum + [0.2126, 0.7152, 0.0722][i] * v, 0),
            }));
            const broken = shades.filter((s, i) => i > 0 && s.lum >= shades[i - 1].lum);
            return broken.length ? pendente(broken.map(s => `${file}:${lineAt(text, s.index)}`), `${plural(broken.length, 'tom', 'tons')} fora de ordem`) : feito();
        },
    },
    {
        id: 'UI-03', area: 'Visual e consistência', prioridade: 'Média', regra: 'primary-action',
        titulo: 'Botões principais com o mesmo verde e o mesmo hover',
        porque: 'Alguns botões usam bg-brand-500 com hover mais escuro; o padrão é bg-brand-600 com hover mais claro.',
        como: 'Padronize em bg-brand-600 hover:bg-brand-500 (ou em .tm-button) depois de corrigir UI-02.',
        // Só o fundo base: hover:bg-brand-500 é o hover correto do padrão.
        check: () => byHits(find(VIEWS, tagPattern('a|button'), m => /(?:^|\s)bg-brand-500(?:\s|$)/.test(attr(m[2], 'class') || ''), { mask: true }), 'botão', 'botões'),
    },
    {
        id: 'UI-04', area: 'Visual e consistência', prioridade: 'Média', regra: 'color-semantic',
        titulo: 'Cores de texto, borda e fundo do CSS vindas dos tokens',
        porque: 'storefront.css mistura hex soltos (#101216, #1b1e25, #353a43...) com os tokens do theme.css.',
        como: 'Troque cada hex por rgb(var(--tm-...)); gradientes decorativos podem ficar.',
        check: () => byHits(find(CSS, /(?:^|[;{\s])((?:color|border(?:-[a-z]+)?|background(?:-color)?)\s*:\s*[^;}]*#[0-9a-fA-F]{3,8}[^;}]*)/m,
            m => !m[1].includes('gradient')), 'declaração', 'declarações'),
    },
    {
        id: 'UI-05', area: 'Visual e consistência', prioridade: 'Média', regra: 'color-semantic',
        titulo: 'Selo de administrador sem a cor de erro',
        porque: 'O selo "Administrador" no perfil é vermelho, cor que o site usa para erro e ação destrutiva.',
        como: 'Use uma cor neutra ou a cor de marca no selo de papel do usuário.',
        check: () => /isAdmin\(\)\s*\?\s*'bg-rose/.test(read(view('profile/edit')) || '') ? pendente([lineOf(view('profile/edit'), /isAdmin\(\)\s*\?\s*'bg-rose/)], 'selo vermelho') : feito(),
    },
    {
        id: 'UI-06', area: 'Visual e consistência', prioridade: 'Média', regra: 'empty-states',
        titulo: 'Estados vazios com o componente x-empty-state',
        porque: 'Carrinho e chat montam o estado vazio à mão, com emoji, e o do chat não tem link para o catálogo.',
        como: 'Troque pelo <x-empty-state> com título, explicação e ação.',
        check: () => byHits([['cart/index', 'Seu carrinho está vazio'], ['chat/index', 'Nenhuma conversa iniciada']]
            .filter(([name]) => !contains(view(name), '<x-empty-state'))
            .map(([name, needle]) => lineOf(view(name), needle)), 'tela', 'telas'),
    },
    {
        id: 'UI-07', area: 'Visual e consistência', prioridade: 'Média', regra: 'consistency',
        titulo: 'Campos do admin e do "Quero vender" no padrão visual',
        porque: 'Nessas telas os campos não têm borda e quase somem sobre o fundo (contraste de componente abaixo de 3:1).',
        como: 'Use as mesmas classes dos outros formulários: bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.',
        check: () => byHits(find([view('admin/users/index'), view('seller/application')], tagPattern('input|select|textarea'), m => {
            if (/type="(hidden|checkbox|radio)"/.test(m[2])) return false;
            return !/\bborder\b/.test(attr(m[2], 'class') || '');
        }, { mask: true }), 'campo', 'campos'),
    },
    {
        id: 'UI-08', area: 'Visual e consistência', prioridade: 'Média', regra: 'consistency',
        titulo: 'Contas do admin em lista compacta, não um formulário inteiro por conta',
        porque: 'Cada conta vira um cartão com selects e um botão verde; não escala com muitos usuários.',
        como: 'Use uma tabela (com rolagem própria no celular) e edição em linha ou numa página de detalhe.',
        check: () => contains(view('admin/users/index'), '<table') ? feito() : pendente([lineOf(view('admin/users/index'), '@forelse')], 'um formulário por conta'),
    },
    {
        id: 'UI-09', area: 'Visual e consistência', prioridade: 'Baixa', regra: 'consistency',
        titulo: 'Telas do comprador sem jargão técnico',
        porque: 'Termos como "webhook assíncrono" e "gateway" não fazem sentido para quem está comprando.',
        como: 'Troque por linguagem do usuário: "processador de pagamento", "confirmação automática".',
        check: () => byHits(find(VIEWS.filter(f => /\/(checkout|orders|cart|listings)\//.test(f) || f.endsWith('order-review.blade.php')),
            /webhook|\bgateway\b|\bGMV\b|Gross Merchandise/i,
            // Ignora código PHP ($payment->gateway, chaves 'gateway') e conta só texto exibido.
            (m, text) => !/(->|\$|['"])$/.test(text.slice(Math.max(0, m.index - 2), m.index)) && !/^\w*['"]/.test(text.slice(m.index + m[0].length))), 'termo', 'termos'),
    },
    {
        id: 'UI-10', area: 'Visual e consistência', prioridade: 'Baixa', regra: 'consistency',
        titulo: 'Textos em caixa de frase, sem Iniciais Maiúsculas',
        porque: 'Parte das telas usa "Finalização do Pedido", "Valor Total a Pagar"; o resto usa frase normal.',
        como: 'Deixe só a primeira palavra e nomes próprios em maiúscula.',
        check: () => {
            const proper = /TheMerchant|Trade URL|Counter-Strike|League of Legends|Mercado Pago|AbacatePay|PIX|Discord/;
            return byHits(find(VIEWS, />\s*([A-ZÀ-Ý][\wÀ-ÿ-]*(?:\s+(?:de|do|da|dos|das|e|a|o|ao|à|para|com|por|em|no|na)?\s*[A-ZÀ-Ý][\wÀ-ÿ-]*){1,})\s*</u,
                m => m[1] !== m[1].toUpperCase() && !proper.test(m[1])), 'texto', 'textos');
        },
    },
];

// ---------- relatório ----------

const PRIORIDADES = ['Crítica', 'Alta', 'Média', 'Baixa'];
const MAX_ONDE = 8;

function run() {
    return ITEMS.map(item => {
        let result;
        try {
            result = item.check();
        } catch (error) {
            result = pendente([], `verificação falhou: ${error.message}`);
        }
        return { ...item, ...result };
    });
}

function render(results, generatedAt) {
    const done = results.filter(r => r.status === 'feito');
    const open = results.filter(r => r.status !== 'feito');
    const lines = [
        '# Backlog de interface do TheMerchant',
        '',
        `> Gerado por \`scripts/audit.mjs\` em ${generatedAt}. Não edite à mão: o status de cada item vem de uma verificação no código.`,
        '> Para mudar um item ou acrescentar outro, edite o catálogo `ITEMS` no script.',
        '',
        `**${done.length} de ${results.length} itens feitos.** ${open.length} em aberto.`,
        '',
        '| Prioridade | Feitos | Em aberto |',
        '| :--- | ---: | ---: |',
        ...PRIORIDADES.map(p => `| ${p} | ${done.filter(r => r.prioridade === p).length} | ${open.filter(r => r.prioridade === p).length} |`),
        '',
        '## Em aberto',
    ];
    for (const prioridade of PRIORIDADES) {
        const group = open.filter(r => r.prioridade === prioridade);
        if (!group.length) continue;
        lines.push('', `### ${prioridade}`, '');
        for (const r of group) {
            lines.push(`- [ ] **${r.id} · ${r.titulo}**${r.status === 'parcial' ? ' (parcial)' : ''} — ${r.resumo}`);
            lines.push(`  - Por quê: ${r.porque} (regra \`${r.regra}\`)`);
            lines.push(`  - Como corrigir: ${r.como}`);
            if (r.onde.length) {
                const shown = r.onde.slice(0, MAX_ONDE).map(o => `\`${o}\``).join(', ');
                lines.push(`  - Onde: ${shown}${r.onde.length > MAX_ONDE ? ` e mais ${r.onde.length - MAX_ONDE}` : ''}`);
            }
        }
    }
    lines.push('', '## Feitos', '');
    for (const r of done) lines.push(`- [x] **${r.id}** ${r.titulo}`);
    lines.push('');
    return lines.join('\n');
}

const stamp = () => new Date().toLocaleString('sv-SE', { timeZone: 'America/Sao_Paulo' }).slice(0, 16);
const body = text => text.replace(/em \d{4}-\d{2}-\d{2} \d{2}:\d{2}\./, 'em <data>.');

function previousStatus() {
    try {
        return JSON.parse(fs.readFileSync(STATE, 'utf8')).itens || {};
    } catch {
        return {};
    }
}

function main() {
    const args = process.argv.slice(2);

    if (args.includes('--hook')) {
        let input = '';
        try {
            input = fs.readFileSync(0, 'utf8');
        } catch {
            // sem stdin: segue como execução normal
        }
        let payload = {};
        try {
            payload = JSON.parse(input || '{}');
        } catch {
            return;
        }
        const edited = payload.tool_input?.file_path || payload.tool_input?.filePath || '';
        if (!RELEVANT.test(edited)) return;
    }

    const results = run();
    const before = previousStatus();
    const changes = results
        .filter(r => before[r.id] && before[r.id] !== r.status)
        .map(r => `${r.id} ${before[r.id]} → ${r.status}`);
    const done = results.filter(r => r.status === 'feito').length;
    const summary = `Backlog de UI: ${done}/${results.length} feitos.`;

    if (args.includes('--check')) {
        console.log(summary);
        for (const r of results.filter(r => r.status !== 'feito')) console.log(`  [${r.prioridade}] ${r.id} ${r.titulo} — ${r.resumo}`);
        return;
    }

    const current = fs.existsSync(BACKLOG) ? fs.readFileSync(BACKLOG, 'utf8') : '';
    const next = render(results, stamp());
    if (body(next) !== body(current)) fs.writeFileSync(BACKLOG, next);
    fs.writeFileSync(STATE, `${JSON.stringify({ itens: Object.fromEntries(results.map(r => [r.id, r.status])) }, null, 2)}\n`);

    if (args.includes('--hook')) {
        // Só avisa o Claude quando algum item muda de status, para não poluir a conversa.
        if (changes.length) {
            console.log(JSON.stringify({
                hookSpecificOutput: {
                    hookEventName: 'PostToolUse',
                    additionalContext: `${summary} Mudaram: ${changes.join('; ')}. Detalhes em .claude/skills/themerchant-ui-backlog/BACKLOG.md.`,
                },
            }));
        }
        return;
    }

    console.log(summary);
    if (changes.length) console.log(`Mudaram: ${changes.join('; ')}`);
    console.log(`Backlog em ${path.relative(ROOT, BACKLOG)}`);
}

try {
    main();
} catch (error) {
    // Um erro aqui nunca deve travar a edição que disparou o hook.
    console.error(`audit.mjs: ${error.message}`);
}
