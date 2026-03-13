<div id="back-to-top">
    <a class="p-0 btn bg-primary btn-sm position-fixed top border-0 rounded-circle text-white" id="top" href="javascript:void(0)">
        <i class="ph ph-caret-up"></i>
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('back-to-top');
    var anchor = btn.querySelector('a');
    // Always start hidden
    btn.style.setProperty('display', 'none', 'important');
    btn.classList.remove('animate__fadeIn');
    btn.classList.add('animate__fadeOut');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            btn.style.setProperty('display', 'block', 'important');
            btn.classList.remove('animate__fadeOut');
            btn.classList.add('animate__fadeIn');
        } else {
            btn.style.setProperty('display', 'none', 'important');
            btn.classList.remove('animate__fadeIn');
            btn.classList.add('animate__fadeOut');
        }
    });
    anchor.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>

