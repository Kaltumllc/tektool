    </main>

    <footer class="app-footer">
        <div class="app-footer-inner">
            <p>
                © <?= date('Y') ?> <strong>TekTool</strong> — C&amp;W Services Field Tech Platform
            </p>

            <span>
                Built by Kaltum LLC
            </span>
        </div>
    </footer>

</div>

<script src="/assets/js/main.js"></script>

<script>
function toggleNav() {
    const menu = document.getElementById('navMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
}
</script>

</body>
</html>