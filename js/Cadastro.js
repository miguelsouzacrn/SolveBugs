function toggleSenha() {
    const input = document.getElementById("senha");
    input.type = input.type === "password" ? "text" : "password";
}

function validar() {
    const senha = document.getElementById("senha");

    if (senha.value.length < 4) {
        senha.classList.add("input-error");

        setTimeout(() => {
            senha.classList.remove("input-error");
        }, 300);

        return false;
    }
}