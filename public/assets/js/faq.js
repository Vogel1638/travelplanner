function toggleFaq(element) {
    const faqItem = element.closest('.faq-item');
    const answer = faqItem.querySelector('.faq-answer');
    const isActive = faqItem.classList.contains('active');
    
    // Schließe alle aktiven FAQs
    document.querySelectorAll('.faq-item.active').forEach(item => {
      const activeAnswer = item.querySelector('.faq-answer');
      item.classList.remove('active');
      activeAnswer.style.maxHeight = "0"; 
    });
  
    // Öffne das geklickte FAQ (wenn es nicht bereits aktiv ist)
    if (!isActive) {
      faqItem.classList.add('active');
      answer.style.maxHeight = "500px"; 
    }
  }
  