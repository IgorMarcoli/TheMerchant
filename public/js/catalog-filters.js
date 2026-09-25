// Progressive enhancement: the GET form also works without JavaScript.
window.catalogFilters = () => ({
    loading: false,
    error: '',
    tags: [],
    rangeMax: 1000,
    timer: null,
    controller: null,
    generation: 0,
    init() {
        this.sync();
    },
    destroy() {
        clearTimeout(this.timer);
        this.controller?.abort();
    },
    sync() {
        const form = this.$refs.form;
        this.rangeMax = Math.max(1000, Number(form.elements.preco_min.value), Number(form.elements.preco_max.value));
        for (const bound of ['min', 'max']) {
            const range = form.querySelector('[data-price="' + bound + '"]');
            range.max = this.rangeMax;
            range.value = form.elements['preco_' + bound].value || (bound === 'min' ? 0 : this.rangeMax);
        }
        const labels = {busca: 'Busca', tipo: 'Tipo', jogo: 'Jogo', categoria: 'Categoria', preco_min: 'Preço mínimo', preco_max: 'Preço máximo', nota_min: 'Nota mínima', ordem: 'Ordenação'};
        this.tags = Array.from(new FormData(form).entries())
            .filter(([name, value]) => labels[name] && value !== '' && !(name === 'ordem' && value === 'recentes'))
            .map(([name, value]) => ({
                name,
                label: labels[name] + ': ' + (form.elements[name].selectedOptions?.[0]?.textContent || value),
            }));
    },
    invalidate() {
        clearTimeout(this.timer);
        this.controller?.abort();
        ++this.generation;
        this.loading = false;
    },
    changed(event) {
        if (event.target.dataset.price) {
            this.$refs.form.elements['preco_' + event.target.dataset.price].value = event.target.value;
        }
        this.invalidate(); // Invalidate immediately, including during the debounce window.
        this.sync();
        this.timer = setTimeout(() => this.search(), 350);
    },
    remove(name) {
        this.$refs.form.elements[name].value = name === 'ordem' ? 'recentes' : '';
        this.sync();
        this.search();
    },
    clear() {
        for (const field of this.$refs.form.elements) {
            if (field.name) field.value = field.name === 'ordem' ? 'recentes' : '';
        }
        this.sync();
        this.search();
    },
    restore() {
        const params = new URL(window.location.href).searchParams;
        for (const field of this.$refs.form.elements) {
            if (field.name) field.value = params.get(field.name) || (field.name === 'ordem' ? 'recentes' : '');
        }
        this.sync();
        this.search(window.location.href, false);
    },
    paginate(event) {
        const link = event.target.closest('a');
        if (!link || !link.closest('nav') || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
        event.preventDefault();
        this.search(link.href, true, true);
    },
    async search(href = null, push = true, focus = false) {
        this.invalidate();
        const form = this.$refs.form;
        this.sync();
        if (!form.reportValidity()) return;
        const min = form.elements.preco_min.value;
        const max = form.elements.preco_max.value;
        if (min !== '' && max !== '' && Number(min) > Number(max)) {
            this.error = 'O preço máximo deve ser maior ou igual ao mínimo.';
            return;
        }
        const url = new URL(href || form.action, window.location.href);
        if (!href) {
            url.search = '';
            for (const [name, value] of new FormData(form)) {
                if (value !== '' && !(name === 'ordem' && value === 'recentes')) url.searchParams.set(name, value);
            }
        }
        const generation = this.generation;
        this.controller = new AbortController();
        this.loading = true;
        this.error = '';
        try {
            const response = await fetch(url, {signal: this.controller.signal, headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'}});
            if (!response.ok) throw new Error('request');
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const results = html.querySelector('#catalog-results');
            if (!results) throw new Error('response');
            if (generation !== this.generation) return;
            this.$refs.results.innerHTML = results.innerHTML;
            if (push && url.href !== window.location.href) history.pushState({}, '', url);
            if (focus) {
                this.$refs.results.tabIndex = -1;
                this.$refs.results.focus();
            }
        } catch (error) {
            if (generation === this.generation && error.name !== 'AbortError') {
                this.error = 'Não foi possível atualizar o catálogo. Confira os filtros e tente novamente.';
            }
        } finally {
            if (generation === this.generation) this.loading = false;
        }
    },
});
