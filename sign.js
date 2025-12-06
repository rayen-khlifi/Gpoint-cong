const form = document.getElementById('signupForm');
const errorMsg = document.getElementById('errorMsg');

form.addEventListener('submit', function(e) {
  e.preventDefault();

  const username = document.getElementById('username').value.trim();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirmPassword').value;

  if (password !== confirmPassword) {
    errorMsg.textContent = "Les mots de passe ne correspondent pas.";
    return;
  }

  if (password.length < 6) {
    errorMsg.textContent = "Le mot de passe doit contenir au moins 6 caractères.";
    return;
  }

  errorMsg.textContent = "";
  alert("Inscription réussie !");
  form.reset();
});


