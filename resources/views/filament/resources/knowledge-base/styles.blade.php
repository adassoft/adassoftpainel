<style>
    /* Aggressively target and hide Trix/RichEditor attachment captions */
    trix-editor figcaption,
    trix-editor .attachment__caption,
    .trix-content figcaption,
    .trix-content .attachment__caption,
    [data-trix-attachment] figcaption,
    .attachment__name,
    .attachment__size {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        width: 0 !important;
        pointer-events: none !important;
        font-size: 0 !important;
        color: transparent !important;
    }
</style>

<script>
    // MutationObserver to ensure captions are hidden even if added dynamically
    document.addEventListener('DOMContentLoaded', () => {
        const hideCaptions = () => {
            const selectors = [
                'trix-editor figcaption',
                '.attachment__caption',
                '.attachment__name',
                '.attachment__size'
            ];

            selectors.forEach(selector => {
                document.querySelectorAll(selector).forEach(el => {
                    el.style.display = 'none';
                    el.style.visibility = 'hidden';
                });
            });
        };

        // Initial check
        hideCaptions();

        // Observe changes
        const observer = new MutationObserver(hideCaptions);
        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>