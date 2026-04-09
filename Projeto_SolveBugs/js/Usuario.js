let solucoes = JSON.parse(localStorage.getItem("solucoes")) || [];
let historico = JSON.parse(localStorage.getItem("historico")) || [];

function salvar(){
localStorage.setItem("solucoes", JSON.stringify(solucoes));
localStorage.setItem("historico", JSON.stringify(historico));
}

function notificar(msg){

let n=document.createElement("div");
n.className="notificacao";
n.innerText=msg;

document.body.appendChild(n);

setTimeout(()=>{
n.remove();
},3000);

}

function adicionarHistorico(texto){

historico.unshift({
texto:texto,
data:new Date().toLocaleString()
});

salvar();

}

function novaSolucao(){

let nome = prompt("Digite o nome da solução");

if(!nome) return;

solucoes.push({
nome:nome,
favorito:false
});

adicionarHistorico("Criou solução: "+nome);

salvar();

mostrarSolucoes();

notificar("Solução criada");

}

function mostrarSolucoes(){

document.getElementById("titulo").innerText="Minhas Soluções";

let conteudo=document.getElementById("conteudo");

conteudo.innerHTML="";

if(solucoes.length===0){

conteudo.innerHTML=`<div class="vazio">Nenhuma solução criada</div>`;

return;

}

let html="";

solucoes.forEach((s,i)=>{

html+=`

<div class="card">

<h3>${s.nome}</h3>

<button class="fav" onclick="favoritar(${i})">
${s.favorito ? "★ Remover favorito" : "☆ Favoritar"}
</button>

<button class="delete" onclick="remover(${i})">
Excluir
</button>

</div>

`;

});

conteudo.innerHTML=html;

}

function favoritar(i){

solucoes[i].favorito=!solucoes[i].favorito;

adicionarHistorico(
(solucoes[i].favorito ? "Favoritou: " : "Removeu favorito: ")
+solucoes[i].nome
);

salvar();

mostrarSolucoes();

}

function remover(i){

if(!confirm("Deseja excluir essa solução?")) return;

adicionarHistorico("Removeu solução: "+solucoes[i].nome);

solucoes.splice(i,1);

salvar();

mostrarSolucoes();

notificar("Solução removida");

}

function mostrarFavoritos(){

document.getElementById("titulo").innerText="Favoritos";

let conteudo=document.getElementById("conteudo");

conteudo.innerHTML="";

let favoritos = solucoes.filter(s=>s.favorito);

if(favoritos.length===0){

conteudo.innerHTML=`<div class="vazio">Nenhum favorito</div>`;

return;

}

let html="";

favoritos.forEach(s=>{

html+=`

<div class="card">

<h3>${s.nome}</h3>

<p>⭐ Favorito</p>

</div>

`;

});

conteudo.innerHTML=html;

}

function mostrarHistorico(){

document.getElementById("titulo").innerText="Histórico";

let conteudo=document.getElementById("conteudo");

conteudo.innerHTML="";

if(historico.length===0){

conteudo.innerHTML=`<div class="vazio">Histórico vazio</div>`;

return;

}

let html="";

historico.forEach(h=>{

html+=`

<div class="card">

<p>${h.texto}</p>
<small>${h.data}</small>

</div>

`;

});

conteudo.innerHTML=html;

}

document.getElementById("campoBusca").addEventListener("input",function(){

let busca=this.value.toLowerCase();

let conteudo=document.getElementById("conteudo");

let html="";

solucoes
.filter(s=>s.nome.toLowerCase().includes(busca))
.forEach((s,i)=>{

html+=`

<div class="card">

<h3>${s.nome}</h3>

<button class="fav" onclick="favoritar(${i})">
${s.favorito ? "★ Remover favorito" : "☆ Favoritar"}
</button>

<button class="delete" onclick="remover(${i})">
Excluir
</button>

</div>

`;

});

conteudo.innerHTML=html;

});

mostrarSolucoes();

function editar(i){

let novoNome = prompt("Editar solução", solucoes[i].nome);

if(!novoNome) return;

solucoes[i].nome = novoNome;

salvar();

mostrarSolucoes();

}

solucoes.sort((a,b)=>b.data-a.data)