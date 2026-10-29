window.listingForm = function (initial) {
    return {
        gameId: initial.gameId,
        categoryId: initial.categoryId,
        categories: initial.categories,
        existing: initial.existing,
        selectedFiles: [],
        previews: [],
        primary: initial.primary,
        photoError: '',

        get compatibleCategories() {
            return this.categories.filter(category => category.game_id === null || String(category.game_id) === this.gameId);
        },
        get isService() {
            return this.categories.find(category => String(category.id) === this.categoryId)?.type === 'service';
        },
        get photos() {
            return [...this.existing, ...this.selectedFiles.map((file, index) => ({
                token: `new:${index}`, name: file.name, url: this.previews[index],
            }))];
        },
        init() {
            this.ensurePrimary();
        },
        selectFiles(input) {
            this.revokePreviews();
            this.selectedFiles = Array.from(input.files);
            this.previews = this.selectedFiles.map(file => URL.createObjectURL(file));
            this.validatePhotos();
            this.ensurePrimary();
        },
        validatePhotos() {
            this.photoError = '';
            if (this.photos.length > 6) this.photoError = 'Mantenha no máximo 6 fotos.';
            else if (this.selectedFiles.some(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type))) {
                this.photoError = 'Use imagens JPG, PNG ou WebP.';
            } else if (this.selectedFiles.some(file => file.size > 4096 * 1024)) {
                this.photoError = 'Cada foto pode ter no máximo 4 MB.';
            }
        },
        removePhoto(token) {
            if (token.startsWith('existing:')) {
                this.existing = this.existing.filter(photo => photo.token !== token);
            } else {
                const index = Number(token.split(':')[1]);
                const primaryFile = this.primary.startsWith('new:') ? this.selectedFiles[Number(this.primary.split(':')[1])] : null;
                URL.revokeObjectURL(this.previews[index]);
                this.selectedFiles.splice(index, 1);
                this.previews.splice(index, 1);
                const transfer = new DataTransfer();
                this.selectedFiles.forEach(file => transfer.items.add(file));
                this.$refs.files.files = transfer.files;
                if (primaryFile) {
                    const position = this.selectedFiles.indexOf(primaryFile);
                    this.primary = position < 0 ? '' : `new:${position}`;
                }
            }
            this.validatePhotos();
            this.ensurePrimary();
        },
        ensurePrimary() {
            if (!this.photos.some(photo => photo.token === this.primary)) this.primary = this.photos[0]?.token || '';
        },
        revokePreviews() {
            this.previews.forEach(url => URL.revokeObjectURL(url));
        },
        destroy() {
            this.revokePreviews();
        },
    };
};
