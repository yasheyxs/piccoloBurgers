
<button id="btn-scroll-up" class="btn-scroll-up" aria-label="Volver arriba">
  <i class="fa-solid fa-chevron-up"></i>
</button>

<style>
.btn-scroll-up {
  position: fixed;
  bottom: 30px; 
  right: 30px;
  background-color: var(--main-gold, #fac30c);
  color: #000;
  font-size: 1.8rem;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: none; 
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  z-index: 999;
  opacity: 0.6;
  transition: transform 0.3s, box-shadow 0.3s, opacity 0.3s;
  border: none;
}

.btn-scroll-up:hover {
  background-color: var(--gold-hover, #e0ae00);
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(0,0,0,0.4);
  opacity: 1;
  color: #000;
}
</style>

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
