# Prova de Conceito (POC) - Estudo com IA de Documentos Próprios

## Objetivo
Criar uma solução que permita o estudo e análise de documentos próprios utilizando inteligência artificial, garantindo a privacidade dos dados ao evitar o envio para serviços externos. O projeto será apresentado ao prefeito/secretário como um conceito para futuras implementações.

## Tecnologias Utilizadas
- **Frontend**: Laravel Blade (interface simples)
- **Backend**: Laravel, Ollama, pgvector
- **Banco de Dados**: PostgreSQL com extensão pgvector

## Funcionalidades
### Frontend
- Página única com interface básica.
- Campo para seleção e upload de arquivos PDF.
- Área de chat para interação com a IA sobre o documento enviado.

### Backend
1. **Upload do PDF**:
   - O sistema recebe o arquivo enviado pelo usuário.
   - O conteúdo do PDF é extraído e processado.
2. **Pré-processamento**:
   - O documento é fragmentado (chunking) em trechos menores.
   - Geração de embeddings para cada trecho utilizando Ollama.
   - Armazenamento dos embeddings no banco de dados utilizando pgvector.
3. **Consulta e Resposta**:
   - O usuário envia uma pergunta pelo chat.
   - O sistema gera o embedding da pergunta e busca os trechos mais relevantes no pgvector.
   - A resposta é gerada e apresentada ao usuário na interface de chat.

## Considerações
- O projeto foca na privacidade e controle dos dados, evitando dependência de serviços externos.
- Arquitetura flexível para futuras melhorias e expansões.
- Base sólida para possível implementação em larga escala dentro da prefeitura.

## Bibliotecas necessárias
- composer require smalot/pdfparser
- composer require pgvector/pgvector
- php artisan vendor:publish --tag="pgvector-migrations" (publicar a extensão no Laravel)
- CREATE EXTENSION IF NOT EXISTS vector; (ativar a extensão pgvector no postgre)
