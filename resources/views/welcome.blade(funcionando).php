<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POC - Estudo com IA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="header-container">
        <h1 class="text-center mb-4">POC - Estudo com IA</h1>
        <div class="my-row" id="card_upload">
            <div class="col-12">
                <div class="shadow-sm p-4">
                    <form id="upload-form" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="pdfFile" class="form-label">Selecione um arquivo PDF:</label>
                            <input class="form-control" type="file" id="pdfFile" accept="application/pdf" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </form>
                    <div id="upload-message" class="mt-3 text-success"></div>
                </div>
            </div>
        </div>

        <div class="row d-flex shadow-sm p-4" id="content-section">
            <div class="col-3" id="card_resumo_pdf"></div>
            <div class="col-9" id="card_chat">
                <h3>Converse com a IA</h3>
                <div id="chat-box" class="border rounded p-3 bg-white" style="height: 300px; overflow-y: auto;">
                </div>
                <div class="input-group mt-3" id="enviar_prompt">
                    <input type="text" id="userInput" class="form-control" placeholder="Digite sua pergunta...">
                    <button class="btn btn-primary" id="sendMessage">Enviar</button>
                </div>
            </div>
        </div>

    </div>
</body>

<script>

    document.getElementById("upload-form").addEventListener("submit", function (event) {
        event.preventDefault();

        let formData = new FormData();
        let fileInput = document.getElementById("pdfFile");
        let csrfToken = document.querySelector('input[name="_token"]').value; // Captura o token CSRF

        if (!fileInput.files.length) {
            document.getElementById("upload-message").classList.add("text-danger");
            document.getElementById("upload-message").textContent = "Por favor, selecione um arquivo.";
            return;
        }

        formData.append("pdfFile", fileInput.files[0]);

        fetch("/upload", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken // Envia o token CSRF no cabeçalho
            },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                let uploadMessage = document.getElementById("upload-message");
                if (data.error) {
                    uploadMessage.classList.add("text-danger");
                    uploadMessage.textContent = data.error;
                } else {
                    uploadMessage.classList.remove("text-danger");
                    uploadMessage.classList.add("text-success");
                    uploadMessage.textContent = data.message;
                    checkStatus(data.id);
                }
            })
            .catch(error => {
                document.getElementById("upload-message").classList.add("text-danger");
                document.getElementById("upload-message").textContent = `Erro ao enviar o arquivo: ${error.message}`;
                console.log(error.message)
            });
    });

    function checkStatus(id) {
        fetch(`/status/${id}`)
            .then(response => response.json())
            .then(data => {
                let uploadMessage = document.getElementById("upload-message");
                if (data.status !== "Concluído") {
                    let percent = parseFloat(data.percent * 100).toFixed(2);
                    uploadMessage.textContent = `${data.status} - ${percent}%`;
                    setTimeout(() => checkStatus(id), 1000);
                } else {
                    uploadMessage.classList.add("text-success");
                    uploadMessage.textContent = "Processamento concluído!";
                    document.getElementById("card_upload").style.display = "none";
                    fetchMetadata(id);
                }
            })
            .catch(error => {
                console.error("Erro ao verificar status:", error);
            });
    }

    function fetchMetadata(id) {
        document.getElementById("content-section").classList.add("d-flex");
        document.getElementById("content-section").classList.remove("d-none");
        fetch(`/metadata/${id}`) // Endpoint que retorna os metadados do PDF
            .then(response => response.json())
            .then(data => {
                const resumoCard = document.getElementById("card_resumo_pdf");
                resumoCard.innerHTML = `
                        <h3>Resumo</h3>
                        <div class="mb-2">
                            <label class="fw-bold">Nome:</label>
                            <span>${data.filename}</span>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold">Título:</label>
                            <span>${data.title || "N/A"}</span>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold">Autor:</label>
                            <span>${data.author || "N/A"}</span>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold">Fonte:</label>
                            <span>${data.source || "N/A"}</span>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold">Produtor:</label>
                            <span>${data.producer || "N/A"}</span>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold">Páginas:</label>
                            <span>${data.pages || "N/A"}</span>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold">Enviado em:</label>
                            <span>${new Date(data.created_at).toLocaleString()}</span>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold">Atualizado em:</label>
                            <span>${new Date(data.updated_at).toLocaleString()}</span>
                        </div>
                    `;
                // <a href="${data.path}" class="btn btn-secondary mt-2" target="_blank">Baixar PDF</a>
            })
            .catch(error => {
                console.error("Erro ao buscar metadados:", error);
            });
    }

    document.getElementById("sendMessage").addEventListener("click", function (event) {
        event.preventDefault();
        sendMessage();
    });

    document.getElementById("userInput").addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            sendMessage();
        }
    });

    function sendMessage() {
        const message = document.getElementById("userInput").value;
        if (!message) return;

        const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
        const chatBox = document.getElementById("chat-box");
        document.getElementById("enviar_prompt").classList.add("d-none");
        document.getElementById("enviar_prompt").classList.remove("d-flex");

        document.getElementById("userInput").value = "";
        chatBox.innerHTML = `<div><strong>Você: </strong>${message}</div>`;
        chatBox.innerHTML += `<div><strong>IA: </strong>Processando <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span></div>`;

        fetch("/userInput", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify({ userInput: message })
        })
            .then(response => response.json())
            .then(data => {
                chatBox.innerHTML = `<div><strong>Você:</strong> ${message}</div>`;
                chatBox.innerHTML += `<div><strong>IA:</strong> ${data.response}</div>`;
            })
            .catch(error => {
                chatBox.innerHTML = `<div><strong>Você:</strong> ${message}</div>`;
                chatBox.innerHTML += `<div><strong>IA:</strong> Error: ${error}</div>`;
            });
        document.getElementById("enviar_prompt").classList.add("d-flex");
        document.getElementById("enviar_prompt").classList.remove("d-none");
    }

    onload = () => {
        document.getElementById("content-section").classList.add("d-none");
        document.getElementById("content-section").classList.remove("d-flex");
    };

</script>

<style>
    .header-container {
        margin: 1rem;
        width: auto;
        box-sizing: border-box;
        background-color: ghostwhite;
        border-radius: 5px;
    }

    body {
        background-color: white;
    }
</style>

</body>

</html>