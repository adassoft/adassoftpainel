<style>
    /* 
       Aggressive CSS to hide Trix attachment metadata/links AND disable image clicking 
    */

    /* --- HIDING TEXT/LINKS BELOW IMAGE --- */
    
    /* Hide caption containers */
    figure.attachment figcaption,
    .trix-content figcaption,
    .trix-content .attachment__caption,
    .attachment__metadata {
        display: none !important;
    }

    /* Hide specific text elements */
    .attachment__name,
    .attachment__size {
        display: none !important;
    }

    /* Hide links inside figure, especially the download/view link */
    figure.attachment a.attachment__link,
    figure.attachment .attachment__caption a {
        display: none !important;
    }

    /* --- DISABLE CLICK ON IMAGE COMPONENT --- */

    /* 
       Target the generic attachment figure to prevent pointer events (clicks).
       WARNING: This disables ALL interaction with the figure (resizing might be affected if UI depends on clicking).
       However, Trix usually handles resizing via a separate overlay or handles.
       If we just want to prevent the "link" behavior:
    */
    
    figure.attachment {
        /* Hide any stray text via zero font size */
        font-size: 0 !important; 
        line-height: 0 !important;
    }

    /* 
       Target the image itself. 
       - pointer-events: none; prevents clicking the image itself.
       - But we often want to select the image in the editor to delete it.
       - Trix wraps images in an anchor tag <a> if it's a link. We want to disable that anchor.
    */
    
    /* Disable all pointer events on links inside attachments */
    figure.attachment a {
        pointer-events: none !important;
        cursor: default !important;
        text-decoration: none !important;
    }

    /* Ensure the image is still visible */
    figure.attachment img {
        display: block !important;
        max-width: 100% !important;
        /* Re-enable pointer events on the image if needed for selection, 
           but since it's likely wrapped in an <a> that we just disabled, 
           we might need to be careful. 
           In Trix, the image IS the content. 
        */
        pointer-events: none !important; /* Prevents opening the image on click */
    }

    /* 
       To still allow SELECTION (cursor placement) around the image, we don't block the figure,
       but usually clicking the image in Trix selects it. 
       If we disable pointer-events on IMG, we can't select it to delete/resize.
       
       COMPROMISE: We want to prevent the LINK behavior (opening URL).
       Usually that's controlled by the wrapping <a> tag or an event listener.
    */
    
    .trix-content figure.attachment a[href] {
        pointer-events: none !important; /* Disables clicking the link */
        cursor: default !important;
    }

    /* Just in case the image isn't wrapped in an A but has an onclick handler, 
       CSS can't stop JS events easily without pointer-events: none.
       We will apply pointer-events: none to the link, but keep it on the figure for selection.
    */

</style>

<script>
    // Javascript to remove 'href' attributes from attachment links to ensure they are dead
    document.addEventListener('DOMContentLoaded', () => {
        const disableAttachmentLinks = () => {
            // Find all links inside attachment figures
            const links = document.querySelectorAll('figure.attachment a');
            links.forEach(link => {
                // If it's a link to a file/image
                if (link.getAttribute('href')) {
                    link.removeAttribute('href'); // Kill the link
                    link.removeAttribute('target');
                    link.style.pointerEvents = 'none'; // Visual disable
                    link.style.cursor = 'default';
                }
            });
        };

        // Run initially
        disableAttachmentLinks();

        // Run on changes (if new images are pasted/uploaded)
        const observer = new MutationObserver(disableAttachmentLinks);
        // Observe subtree to catch new nodes
        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>