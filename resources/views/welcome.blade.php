<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POC - Estudo com IA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="header-container shadow-sm">
        <p class="text-center titulo">POC - Estudo com IA</p>
    </div>

    <div class="header-container shadow-sm text-center">
        <form id="upload-form" enctype="multipart/form-data">
            @csrf
            <div class="row d-flex">
                <div class="col-6">
                    <div class="input-group mt-3" id="enviar_prompt">
                        <input class="form-control" type="file" id="pdfFile" accept="application/pdf" required>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                    <div id="upload-message" class="mt-3 text-success"></div>
                </div>
                <div class="col-6">
                    <select id="pdf-select" class="form-select mt-3" onchange="fetchMetadata(this.value)">
                        <option value="">Selecione um documento</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="header-container shadow-sm">
        <div class="row d-flex" id="content-section">
            <div class="col-3" id="card_resumo_pdf"></div>
            <div class="col-9" id="card_chat">
                <h3>Converse com a IA</h3>
                <div id="chat-box" class="border rounded p-3 bg-white" style="height: 300px; overflow-y: auto;"></div>
                <div class="input-group mt-2" id="enviar_prompt">
                    <input class="form-control" type="text" id="userInput" placeholder="Digite sua pergunta...">
                    <button class="btn btn-primary" id="sendMessage">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <script>

        document.getElementById("upload-form").addEventListener("submit", function (event) {
            event.preventDefault();
            let formData = new FormData();
            let fileInput = document.getElementById("pdfFile");
            if (!fileInput.files.length) return;
            formData.append("pdfFile", fileInput.files[0]);

            fetch("/upload", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Content-Type": "application/json"
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        fetchDocuments();
                    }
                });
        });

        function fetchDocuments() {
            fetch("/documents")
                .then(response => response.json())
                .then(data => {
                    const pdfSelect = document.getElementById("pdf-select");
                    pdfSelect.innerHTML = '<option value="">Selecione um documento</option>';
                    data.forEach(doc => {
                        let option = document.createElement("option");
                        option.value = doc.id;
                        option.textContent = doc.filename;
                        pdfSelect.appendChild(option);
                    });
                });
        }

        function fetchMetadata(id) {
            if (!id) return;
            fetch(`/metadata/${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("card_resumo_pdf").innerHTML = `
                    <h3>Resumo File ID ${data.id}</h3>
                    <div><strong>Nome:</strong> ${data.filename}</div>
                    <div><strong>Título:</strong> ${data.title || "N/A"}</div>
                    <div><strong>Autor:</strong> ${data.author || "N/A"}</div>
                    <div><strong>Produtor:</strong> ${data.producer || "N/A"}</div>
                    <div><strong>Páginas:</strong> ${data.pages || "N/A"}</div>
                    <div><strong>Criação:</strong> ${data.created_at || "N/A"}</div>
                `;
                    // <a href="${data.path}" class="btn btn-secondary mt-2" target="_blank">Baixar PDF</a>
                });
        }

        document.getElementById("sendMessage").addEventListener("click", function () {
            sendMessage();
        });

        document.getElementById("userInput").addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                sendMessage();
            }
        });

        function sendMessage() {
            let message = document.getElementById("userInput").value;
            if (!message) return;

            chatBox.innerHTML = `<div><strong>Você: </strong>${message}</div>`;
            chatBox.innerHTML += `<div><strong>IA: </strong>Carregando <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span></div>`;

            fetch("/userInput", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    userInput: message,
                    docSelecionado: document.getElementById("pdf-select").value
                })
            })
                .then(response => response.json())
                .then(data => {
                    let chatBox = document.getElementById("chat-box");
                    chatBox.innerHTML = `<div><strong>Você: </strong>${message}</div>`;
                    chatBox.innerHTML += `<div><strong>IA: </strong>${data.response}</div>`;
                    chatBox.scrollTop = chatBox.scrollHeight;
                });
        }

        onload = fetchDocuments;

    </script>

    <style>
        .titulo {
            font-size: xx-large;
            margin: 5px !important;
            padding: 5px !important;
        }

        .header-container {
            margin: 20px;
            padding: 5px;
            background-color: ghostwhite;
            border-radius: 5px;
            border: solid 1px lightgray;
        }
    </style>

</body>

</html>