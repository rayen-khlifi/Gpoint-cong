function libre(event) {
    var barem = document.getElementById('em').value.trim();
    var barpas = document.getElementById('aa').value.trim();
  
    if (barem === "") {
      alert("entrer le cordonner ");
      event.preventDefault();
    } else if (barpas === "") {
      alert("Veuillez remplir le mot de passe");
      event.preventDefault();
    }
  }

  