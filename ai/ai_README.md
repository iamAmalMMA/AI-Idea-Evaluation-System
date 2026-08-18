# Smart Idea Evaluation AI

## Overview

This folder contains the local AI inference service used by the **Smart Idea Evaluation System**.

The AI service evaluates submitted ideas and returns a structured JSON evaluation. The PHP backend sends the submitted idea to the local AI service, receives the evaluation result, and uses the result in the web application.

The AI runs locally on the deployment machine. The web application does not depend on an external AI inference API.

---

## AI Architecture

The AI component consists of:

1. A FastAPI application
2. A Llama 3.1 8B base model
3. A LoRA fine-tuning adapter
4. Unsloth for model loading and optimized inference
5. PyTorch for model execution

The AI service communicates with the PHP backend through HTTP.

The integration flow is:

```text
Web Interface
      |
      v
PHP Backend
      |
      v
backend/api/evaluate.php
      |
      v
Local FastAPI AI Service
      |
      v
Llama 3.1 8B + LoRA Adapter
      |
      v
Structured Evaluation JSON
      |
      v
PHP Backend
      |
      v
Web Interface / Database
```

The AI service runs on:

```text
http://localhost:8000
```

---

# Model

## Base Model

The LoRA adapter is based on:

```text
unsloth/Meta-Llama-3.1-8B-Instruct-bnb-4bit
```

The base model is an 8-billion-parameter Llama 3.1 instruction model configured for 4-bit loading.

---

## Fine-Tuning Method

The project uses:

```text
LoRA (Low-Rank Adaptation)
```

LoRA allows the project-specific adaptation to be stored separately from the base model.

The trained adapter is loaded together with the base model during inference.

---

## LoRA Adapter

The trained adapter is hosted on Hugging Face:

```text
iamAmalMMA/llama3_1_8b_lora_ideas
```

The adapter repository contains the trained LoRA weights and the configuration required to load them.

The adapter configuration identifies the base model as:

```text
unsloth/Meta-Llama-3.1-8B-Instruct-bnb-4bit
```

The AI server loads the adapter using the model name configured in:

```text
server.py
```

Specifically:

```python
MODEL_NAME = "iamAmalMMA/llama3_1_8b_lora_ideas"
```

---

## Inference Configuration

The current AI service uses:

| Configuration | Value |
|---|---|
| Maximum sequence length | 2048 |
| Model loading | 4-bit |
| Maximum generated tokens | 1024 |
| Temperature | 0.3 |
| Inference framework | Unsloth |
| API framework | FastAPI |

---

# Project Structure

```text
ai/
├── server.py
├── mock_server.py
├── requirements.txt
├── requirements-ai.txt
├── README.md
└── .gitignore
```

### `server.py`

The actual AI inference service.

It:

- Loads the Llama 3.1 8B model and LoRA adapter.
- Starts the FastAPI application.
- Accepts an idea title and description.
- Generates an evaluation.
- Returns the evaluation as JSON.
- Provides a health-check endpoint.

### `mock_server.py`

A lightweight development and testing server.

It does not load the trained Llama model.

It can be used when testing the PHP/web integration without starting the full AI model.

The actual AI service used for deployment is:

```text
server.py
```

### `requirements-ai.txt`

Contains the dependencies required by the real AI inference service.

### `requirements.txt`

Contains the lightweight dependencies required by the mock server.

### `.gitignore`

Prevents local Python environments and generated Python files from being committed.

---

# Requirements

## Software

The real AI service requires:

- Python
- PyTorch
- Unsloth
- PEFT
- bitsandbytes
- Accelerate
- FastAPI
- Uvicorn
- Pydantic

The complete dependency list is available in:

```text
requirements-ai.txt
```

---

## Hardware

The AI model is an 8B parameter Llama model loaded in 4-bit mode.

A compatible NVIDIA GPU with CUDA support is recommended for practical local inference.

The deployment machine should have sufficient GPU memory and compatible CUDA/PyTorch support for the selected model configuration.

The project intentionally does not include a machine-specific Python virtual environment. The deployment environment should create its own virtual environment and install the required dependencies.

---

# Installation

## 1. Open the AI Directory

From the project root:

```powershell
cd ai
```

---

## 2. Create a Python Virtual Environment

```powershell
python -m venv .venv
```

On Windows, activate it with:

```powershell
.\.venv\Scripts\Activate.ps1
```

If PowerShell prevents activation because of the execution policy, the policy can be changed for the current PowerShell process:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy RemoteSigned
```

Then activate the environment again:

```powershell
.\.venv\Scripts\Activate.ps1
```

---

## 3. Install the AI Dependencies

For the real AI service:

```powershell
pip install -r requirements-ai.txt
```

This installs the dependencies required by `server.py`.

---

# Starting the Real AI Service

From the `ai` directory, with the virtual environment activated:

```powershell
python server.py
```

The service starts on:

```text
http://localhost:8000
```

The first startup may take longer because the model and adapter may need to be downloaded and loaded into memory.

Once the model has loaded successfully, the server can receive evaluation requests.

---

# Health Check

The AI service provides:

```text
GET /health
```

Open:

```text
http://localhost:8000/health
```

Expected response:

```json
{
  "status": "ok",
  "service": "Smart Idea Evaluation AI"
}
```

A successful health check confirms that the FastAPI service is running.

---

# Evaluation API

The main endpoint is:

```text
POST /evaluate
```

The request body must contain:

```json
{
  "title": "Idea title",
  "description": "Idea description"
}
```

The AI processes the title and description and returns a structured evaluation JSON object.

The returned evaluation contains the fields expected by the PHP backend, including:

- Evaluation scores
- Overall score
- Strengths
- Improvement opportunities
- Improved proposal

---

# PHP Backend Connection

The PHP backend communicates with the AI service through:

```text
http://localhost:8000/evaluate
```

The backend configuration uses:

```text
AI_API_URL=http://localhost:8000/evaluate
```

This value is configured in:

```text
backend/.env
```

The repository does not include the actual `.env` file. Instead, the project provides:

```text
backend/.env.example
```

The deployment environment should create its own `.env` based on this example.

---

# End-to-End Evaluation Flow

When a user submits an idea for evaluation:

```text
1. User submits an idea
        |
        v
2. Web interface sends the request
        |
        v
3. PHP backend receives the idea
        |
        v
4. backend/api/evaluate.php
        |
        v
5. Local AI service
   http://localhost:8000/evaluate
        |
        v
6. Llama 3.1 8B + LoRA adapter
        |
        v
7. AI generates structured JSON
        |
        v
8. PHP backend receives the result
        |
        v
9. Evaluation is processed by the application
        |
        v
10. Result is displayed/stored by the web application
```

The AI inference itself remains local to the deployment machine.

---

# Mock Server

## Purpose

`mock_server.py` is provided for development and integration testing.

It allows the web application and PHP backend to be tested without loading the full Llama model.

This is useful when:

- The AI environment is not installed yet.
- The deployment machine does not have a compatible GPU.
- The team is testing frontend/backend integration.
- A faster development server is needed.

The mock server is **not the trained AI model**.

For the actual AI deployment, use:

```text
server.py
```

---

## Starting the Mock Server

From the `ai` directory:

```powershell
python mock_server.py
```

It starts a FastAPI service on:

```text
http://localhost:8000
```

When using the mock server, the PHP backend can continue using:

```text
AI_API_URL=http://localhost:8000/evaluate
```

The mock server should be stopped before starting the real AI server because both services use the same port.

---

# AI Service Port

The default AI service port is:

```text
8000
```

The real AI server and mock server both use this port by default.

Only one of them should be running at a time.

---

# Internet Requirement

The AI service may require internet access during the initial model loading process if the required model files are not already cached on the deployment machine.

The configured LoRA adapter is hosted on Hugging Face:

```text
iamAmalMMA/llama3_1_8b_lora_ideas
```

The adapter repository contains the project-specific LoRA weights.

The base model is:

```text
unsloth/Meta-Llama-3.1-8B-Instruct-bnb-4bit
```

Once the required model files are available locally, normal inference is performed locally.

---

# Model Storage

The project source code does not contain the large model weights.

The deployment machine downloads or accesses the required model and adapter through the configured model references.

This keeps the Git repository and project ZIP reasonably sized while allowing the AI service to reconstruct the required runtime environment.

---

# Troubleshooting

## `ModuleNotFoundError`

If Python reports an error such as:

```text
ModuleNotFoundError: No module named 'fastapi'
```

activate the AI virtual environment and install the dependencies:

```powershell
.\.venv\Scripts\Activate.ps1
pip install -r requirements-ai.txt
```

---

## AI Server Does Not Start

Check:

1. Python is installed.
2. The virtual environment is activated.
3. `requirements-ai.txt` has been installed.
4. The machine has compatible GPU/CUDA support.
5. The required model files can be downloaded/accessed.
6. Port `8000` is not already being used by another process.

---

## Health Endpoint Does Not Respond

Verify that the AI server is running:

```powershell
python server.py
```

Then open:

```text
http://localhost:8000/health
```

---

## PHP Cannot Connect to the AI

Check:

```text
backend/.env
```

and make sure it contains:

```text
AI_API_URL=http://localhost:8000/evaluate
```

Also verify that the AI service is running on port `8000`.

---

## Website Evaluation Does Not Work

Check the services in this order:

```text
MySQL
  |
  v
Apache / XAMPP
  |
  v
PHP Backend
  |
  v
Local AI Service
```

The AI service must be running before submitting an idea that requires AI evaluation.

---

# Security and Deployment Notes

The AI service is intended to run locally as part of the internal system.

The project does not require an external AI inference API for evaluation.

The actual environment configuration files are intentionally excluded from source control.

The repository provides environment templates:

```text
backend/.env.example
frontend/.env.example
```

Create the corresponding `.env` files on the deployment machine according to the project README.

---

# Important Files

| File | Purpose |
|---|---|
| `server.py` | Actual local AI inference service |
| `mock_server.py` | Lightweight AI integration/testing server |
| `requirements-ai.txt` | Dependencies for the real AI service |
| `requirements.txt` | Dependencies for the mock server |
| `README.md` | AI setup and deployment documentation |
| `.gitignore` | Excludes local environments and generated files |

---

# Final AI Deployment

For the actual deployment, use:

```text
server.py
```

with:

```text
requirements-ai.txt
```

The required environment should provide compatible Python, PyTorch, CUDA/GPU, and AI dependencies.

The PHP backend should point to:

```text
http://localhost:8000/evaluate
```

The complete system then operates as:

```text
Web Application
      |
      v
PHP Backend
      |
      v
Local FastAPI AI
      |
      v
Llama 3.1 8B + LoRA
      |
      v
Evaluation JSON
```

The AI inference remains local to the organization's deployment machine.
