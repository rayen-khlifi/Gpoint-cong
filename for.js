
function mdps(event) {
  event.preventDefault(); 
  var mdp = document.getElementById("mdp").value.trim();
  var rmdp = document.getElementById("rmdp").value.trim();
  
  if (mdp === "") {
      alert("Le mot de passe ne peut pas être vide.");
      return;
  }

  if (mdp !== rmdp) {
      alert("Les mots de passe ne correspondent pas !");
  } else {
      alert("Réinitialisation réussie !");
      document.getElementById("resetForm").reset(); 
  }
}


mdps();
function forg(event) {
  var barus = document.getElementById('user').value.trim();
  var barb = document.getElementById('nb').value.trim();
  var barpas = document.getElementById('mdp').value.trim();
  var barrpas = document.getElementById('rmdp').value.trim();


  if (barus=== "") {
    alert("Veuillez remplir user");
    event.preventDefault();
  } else if (barpas === "") {
    alert("Veuillez remplir le mot de passe");
    event.preventDefault(); 
  }
}


       