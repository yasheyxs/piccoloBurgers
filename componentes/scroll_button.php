
<link rel="stylesheet" href="/assets/css/floating-button.css">

<button id="btn-scroll-up" class="floating-btn floating-btn--gold btn-scroll-up" aria-label="Volver arriba">
  <i class="fa-solid fa-chevron-up"></i>
</button>

<script>
  const scrollUpBtn = document.getElementById("btn-scroll-up");

  window.addEventListener("scroll", () => {
    if (window.scrollY > 300) {
      scrollUpBtn.style.display = "flex";
    } else {
      scrollUpBtn.style.display = "none";
    }
  });

  scrollUpBtn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
</script>
