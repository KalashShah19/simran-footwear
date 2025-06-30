function changeImage(element) {
  const mainImage = document.getElementById('currentImage');
  mainImage.src = element.src;
}

document.querySelectorAll('.size-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  });
});
