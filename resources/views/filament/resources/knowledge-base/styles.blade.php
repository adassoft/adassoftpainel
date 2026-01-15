<style>
    /* 
       Aggressive CSS to hide Trix attachment metadata/links.
       We target multiple potential structures to ensure the filename link is hidden.
    */

    /* 1. Target the standard caption container */
    figure.attachment figcaption,
    .trix-content figcaption,
    .trix-content .attachment__caption {
        display: none !important;
    }

    /* 2. Target specific metadata elements (name, size) */
    .attachment__name,
    .attachment__size,
    .attachment__metadata {
        display: none !important;
    }

    /* 3. Target the specific link styling often found in Filament/Trix */
    figure.attachment a.attachment__link {
        display: none !important;
    }

    /* 4. Catch-all for any anchor tag inside a figure that looks like a text link 
       (careful not to hide the image if it's wrapped in an A, though Trix usually doesn't do that for the preview itself in this context) 
    */
    figure.attachment .attachment__caption a {
        display: none !important;
    }

    /* 5. Force hide the entire caption text if it's somehow leaking out */
    figure.attachment {
        /* Ensure the figure behaves like a block but hide text content that isn't the image */
        font-size: 0 !important;
        line-height: 0 !important;
    }

    figure.attachment img {
        /* Restore visibility for the image */
        display: block !important;
        width: auto !important;
        /* Allow responsive sizing */
        max-width: 100% !important;
    }

    /* 6. Specifically for the orange link seen in the screenshot */
    figure.attachment a[href*="storage"],
    figure.attachment a[download] {
        display: none !important;
    }
</style>