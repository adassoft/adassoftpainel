<style>
    /* 
       Hide Trix/RichEditor attachment captions.
       Using CSS only to avoid performance issues with JS observers.
    */
    .trix-content figcaption,
    .trix-content .attachment__caption,
    .fi-fo-rich-editor figcaption,
    .fi-fo-rich-editor .attachment__caption,
    .attachment__name,
    .attachment__size {
        display: none !important;
        opacity: 0 !important;
        pointer-events: none !important;
        height: 0 !important;
        width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
    }
</style>