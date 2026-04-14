/**
 * SEO Analytics — Fullscreen textarea toggle
 */
(function(){
    var activeTarget = null;
    var overlay      = document.getElementById('fullscreen-overlay');
    var fsTextarea   = document.getElementById('fullscreen-textarea');
    var fsLabel      = document.getElementById('fullscreen-label');
    var closeBtn     = document.getElementById('fullscreen-close-btn');

    if (!overlay) return;

    // Open
    document.querySelectorAll('.fullscreen-toggle-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var targetId = this.getAttribute('data-target');
            var original = document.getElementById(targetId);
            activeTarget = original;
            var container = this.closest('.mb-3') || this.closest('.col-12');
            fsLabel.textContent = container ? (container.querySelector('label') ? container.querySelector('label').textContent : '') : '';
            fsTextarea.value = original.value;
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
            fsTextarea.focus();
        });
    });

    // Close
    function closeFullscreen(){
        if(activeTarget){
            activeTarget.value = fsTextarea.value;
        }
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        activeTarget = null;
    }

    closeBtn.addEventListener('click', closeFullscreen);

    // ESC
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape' && overlay.style.display === 'block'){
            closeFullscreen();
        }
    });
})();
