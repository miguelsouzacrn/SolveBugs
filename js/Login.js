const form = document.getElementById("formLogin");

form.addEventListener("submit", function (e) {
    e.preventDefault();

    let usuario = document.getElementById("usuario").value;

    if (usuario.trim() === "") return;

    // salva usuário
    localStorage.setItem("usuarioLogado", usuario);

    // redireciona
    window.location.href = "index.html";
});