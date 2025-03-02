<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POC - Estudo com IA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">POC - Estudo com IA</h1>

        <div class="card shadow-sm p-4">
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

        <div class="mt-5">
            <h3>Converse com a IA</h3>
            <div id="chat-box" class="border rounded p-3 bg-white" style="height: 300px; overflow-y: auto;"></div>
            <div class="input-group mt-3">
                <input type="text" id="userInput" class="form-control" placeholder="Digite sua pergunta...">
                <button class="btn btn-primary" id="sendMessage">Enviar</button>
            </div>
        </div>
    </div>

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
                        // console.log(`Path: ${data.path}`);
                        // console.log(`ID: ${data.id}`);
                        checkStatus(data.id);
                    }
                })
                .catch(error => {
                    document.getElementById("upload-message").classList.add("text-danger");
                    document.getElementById("upload-message").textContent = `Erro ao enviar o arquivo: ${error.message}`;
                });
        });

        function checkStatus(id) {
        fetch(`/api/status/${id}`)
            .then(response => response.json())
            .then(data => {
                let uploadMessage = document.getElementById("upload-message");
                uploadMessage.textContent = `Status: ${data.status} (${data.percent}%)`;

                console.log(`Status: ${data.status}, Percentual: ${data.percent}%`);

                if (data.status !== "concluído") {
                    setTimeout(() => checkStatus(id), 1000);
                } else {
                    uploadMessage.classList.add("text-success");
                    uploadMessage.textContent = "Processamento concluído!";
                }
            })
            .catch(error => {
                console.error("Erro ao verificar status:", error);
            });
    }
    </script>
</body>

</html>