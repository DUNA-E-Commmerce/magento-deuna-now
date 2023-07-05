document.addEventListener('DOMContentLoaded', function() {

  function verificarBoton() {
    var boton = document.getElementById('top-cart-btn-checkout'); 
    clearInterval(verificarBoton);

    if (boton) {

      var originalButton = document.getElementById('top-cart-btn-checkout');
      if (originalButton) {
        var parentElement = originalButton.parentElement;
        var newButton = document.createElement('button');
        
        newButton.id = 'deuna-checkout';
        newButton.type = 'button';
        newButton.classList.add('action', 'primary', 'checkout');
        newButton.title = 'Proceed to Checkout';
        newButton.innerText = 'Proceed to Checkout';

        parentElement.replaceChild(newButton, originalButton);

        var modal = document.getElementById('ModalDeuna'); 

        if (newButton) {
          newButton.addEventListener('click', function (event) {
            modal.style.display = 'block';
          });
        }

        var span = document.getElementById('ModalDeunaClose'); 

        span.addEventListener('click', function (event) {
          modal.style.display = 'none';
        });
          
      }

      clearInterval(verificarInterval);

    } 
  }

  var verificarInterval = setInterval(verificarBoton, 1000);
 

  setTimeout(function() {
    
  }, 3000); 
});

