document.addEventListener('alpine:init', () => {
    Alpine.data('reviewForm', () => ({
        rating: '',
        hover: 0,
        comment: '',
        sending: false,
        saved: null,
        error: '',
        errors: {},
        toast: '',

        async submit(form) {
            if (this.sending || this.saved || !form.reportValidity()) return;
            const payload = new FormData(form);
            this.sending = true;
            this.error = '';
            this.errors = {};
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 15000);
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: payload,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (response.redirected) throw new Error('session');
                if (response.status === 422) {
                    const data = await response.json();
                    this.errors = data.errors || {};
                    this.error = 'Não foi possível publicar. Confira os campos abaixo. Se já enviou em outra aba, recarregue a página.';
                } else if ([401, 419].includes(response.status)) {
                    this.error = 'Sua sessão expirou. Copie seu comentário e recarregue a página para entrar novamente.';
                } else if (response.status === 403) {
                    this.error = 'Esta compra não está disponível para avaliação pela sua conta. Recarregue a página para conferir o estado atual.';
                } else if (!response.ok) {
                    this.error = 'Não foi possível confirmar a publicação. Seu comentário foi mantido; tente novamente ou recarregue para verificar se foi salvo.';
                } else {
                    const data = await response.json();
                    this.saved = data.review;
                    this.toast = data.message;
                    this.$nextTick(() => this.$refs.confirmation.focus());
                }
            } catch {
                this.error = 'A conexão falhou ou a sessão expirou. Seu comentário foi mantido. Tente novamente ou recarregue para verificar se a avaliação foi salva.';
            } finally {
                clearTimeout(timeout);
                this.sending = false;
                if (this.error) this.$nextTick(() => this.$refs.error.focus());
            }
        },
    }));
});
