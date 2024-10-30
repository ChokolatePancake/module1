function openModal(imageUrl) {
  document.getElementById('fullImage').src = imageUrl;
  const modal = document.querySelector('.photo-modal-wrapper');
  modal.classList.add('visible');
}

function closeModal() {
  const modal = document.querySelector('.photo-modal-wrapper');
  modal.classList.remove('visible');
}

// Close modal when click outside the image
document.addEventListener('click', function(event) {
  const modal = document.querySelector('.photo-modal-wrapper');
  if (event.target === modal) {
    closeModal();
  }
});
