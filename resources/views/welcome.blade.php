<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POC - Estudo com IA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <!-- Toastify (Notificações Modernas) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</head>

<body>
    <div class="header-container shadow-sm">
        <p class="text-center titulo"><strong>POC - IA para auxílio dos servidores</strong></p>
    </div>

    <div class="header-container shadow-sm text-center" id="upload-pdf">
        <form id="upload-form" enctype="multipart/form-data">
            @csrf
            <div class="row d-flex">
                <div class="col-6 d-flex align-items-center justify-content-center">
                    <div class="input-group m-3">
                        <input class="form-control" type="file" id="pdfFile" accept="application/pdf" required>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </div>
                <div class="col-6 d-flex align-items-center justify-content-center">
                    <select id="pdf-select" class="form-select m-3" onchange="fetchMetadata(this.value)">
                        <option value="">Selecione um documento</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="header-container shadow-sm p-3">
        <div class="row d-flex" id="content-section">
            <div class="col-3" id="card_resumo_pdf"></div>
            <div class="col-9" id="card_chat">
                <h3>Converse com a AraucarIA - Stream</h3>
                <div id="chat-box" class="border rounded p-3 bg-white" style="height: 400px; overflow-y: auto;"></div>
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
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification(data.error, "error");
                    } else {
                        showNotification(data.message, "success");
                        checkStatus(data.id)
                        fetchDocuments(data.id);
                    }
                })
                .catch(error => {
                    showNotification(error.message, "error");
                });
            fileInput.value = "";
        });

        let ultimoStatus = ""; // Armazena o último status conhecido
        function checkStatus(id) {
            fetch(`/status/${id}`)
                .then(response => response.json())
                .then(data => {
                    let percent = parseFloat(data.percent * 100).toFixed(2);
                    let statusAtual = `${data.status} - ${percent}%`;

                    // Exibir notificação APENAS se o status mudou
                    if (statusAtual !== ultimoStatus) {
                        showNotification(statusAtual, "success");
                        ultimoStatus = statusAtual; // Atualiza o último status
                    }

                    if (data.status !== "Concluído") {
                        setTimeout(() => checkStatus(id), 100);
                    } else {
                        showNotification("Processamento concluído!", "success");
                        fetchDocuments();
                    }
                })
                .catch(error => {
                    console.error("Erro ao verificar status:", error);
                });
        }

        function fetchDocuments(selectedId = null) {
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

                    // Seleciona automaticamente o último documento enviado
                    if (selectedId) {
                        pdfSelect.value = selectedId;
                        fetchMetadata(selectedId); // Carrega os metadados automaticamente
                    }
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

        async function sendMessage() {
            let message = document.getElementById("userInput").value;
            if (!message) return;

            document.getElementById("userInput").value = '';

            let chatBox = document.getElementById("chat-box");
            chatBox.innerHTML = `<strong class="message user-message-title">Você</strong>`;
            chatBox.innerHTML += `<div class="message user-message">${message}</div>`;
            chatBox.innerHTML += `<strong class="message ia-message-title">AraucarIA</strong>`;
            chatBox.innerHTML += `<div id="iaMessage" class="message ia-message">Carregando <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span></div>`;

            let enviarPrompt = document.getElementById("enviar_prompt");
            enviarPrompt.classList.add("d-none");
            enviarPrompt.classList.remove("d-flex");

            let uploadPDF = document.getElementById("upload-pdf");
            uploadPDF.classList.add("d-none");
            uploadPDF.classList.remove("d-flex");

            try {
                const response = await fetch("/userInputStream", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        userInput: message,
                        docSelecionado: document.getElementById("pdf-select").value
                    })
                });

                if (!response.body) {
                    throw new Error("Resposta de stream inválida.");
                }

                const reader = response.body.getReader();
                let decoder = new TextDecoder();
                let iaMessageDiv = document.getElementById("iaMessage");
                iaMessageDiv.innerHTML = ""; // Limpa o "Carregando..."

                let markdownContent = ""; // Acumulador para armazenar a resposta em Markdown

                // Processar o stream em partes corretamente
                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    // Decodificar a resposta recebida
                    let chunk = decoder.decode(value, { stream: true });

                    try {
                        // Quebra a string em múltiplos objetos JSON caso venha mais de um por chunk
                        let jsonStrings = chunk.trim().split("\n").filter(Boolean);

                        jsonStrings.forEach(jsonString => {
                            let parsedData = JSON.parse(jsonString); // Converte para objeto JSON

                            // Verifica se há conteúdo na chave `message.content`
                            if (parsedData.message && parsedData.message.content) {
                                markdownContent += parsedData.message.content; // Acumula a resposta
                                iaMessageDiv.innerHTML = marked.parse(markdownContent); // Converte Markdown para HTML
                                chatBox.scrollTop = chatBox.scrollHeight; // Rola o chat para baixo
                            }
                        });

                    } catch (error) {
                        console.error("Erro ao processar stream:", error, chunk);
                    }
                }

            } catch (error) {
                console.error("Erro ao receber resposta:", error);
                document.getElementById("iaMessage").innerHTML = "Erro ao obter resposta.";
            }

            enviarPrompt.classList.add("d-flex");
            enviarPrompt.classList.remove("d-none");

            uploadPDF.classList.add("d-flex");
            uploadPDF.classList.remove("d-none");
        }

        function showNotification(message, type = "success") {
            Toastify({
                text: message,
                duration: 5000, // 5 segundos
                gravity: "top", // Notificação no topo
                position: "right", // Alinhado à direita
                close: true, // Botão de fechar
                style: {
                    background: type === "success" ? "green" : "red" // Uso correto
                }
            }).showToast();
        }

        onload = fetchDocuments();

    </script>

    <style>
        .titulo {
            font-size: xx-large;
            margin: 5px !important;
            padding: 5px !important;
        }

        .header-container {
            margin: 10px 20px;
            padding: 5px;
            background-color: ghostwhite;
            border-radius: 5px;
            border: solid 1px lightgray;
        }

        #chat-box {
            display: flex;
            flex-direction: column;
            padding: 3px !important;
        }

        .message {
            max-width: 75%;
            padding: 0px;
            border-radius: 10px;
            margin: 0px;
            word-wrap: break-word;
        }

        .user-message-title {
            align-self: flex-end;
            text-align: right;
            padding: 0px !important;
            margin: 0px 15px !important;
        }

        .user-message {
            align-self: flex-end;
            background-color: #007bff;
            color: white;
            text-align: right;
            padding: 10px 15px;
        }

        .ia-message {
            align-self: flex-start;
            background-color: #f1f1f1;
            color: black;
            padding: 10px 15px;
        }

        .ia-message-title {
            align-self: flex-start;
            padding: 0px !important;
            margin: 0px 15px !important;
        }

        .ia-message strong {
            color: #333;
        }

        .ia-message em {
            font-style: italic;
        }

        .ia-message ul {
            padding-left: 20px;
        }
    </style>

</body>

</html>