# Smart Idea Evaluation System

## Overview

The Smart Idea Evaluation System is an internal web-based platform for submitting, managing, and evaluating ideas.

The system combines a PHP/MySQL web application with a locally running Artificial Intelligence (AI) service. The AI evaluates submitted ideas and returns a structured evaluation that is displayed through the web interface.

The system is intended for internal organizational use. Users do not create accounts through the interface; they log in using accounts configured in the database.

---

## Main Features

- User login and authentication
- Idea submission
- Draft idea saving
- Idea management
- AI-powered idea evaluation
- Evaluation scores and explanations
- Overall idea score
- Strengths identification
- Improvement opportunities
- Improved idea proposal
- Idea status management
- Notifications
- Role-based functionality
- Local AI inference without an external AI inference API

---

# System Architecture

The system consists of three main components:

1. Frontend
2. PHP Backend
3. Local AI Service

The overall architecture is:

```text
                         User
                           |
                           v
                    Web Interface
                   (PHP / HTML / JS)
                           |
                           v
                      PHP Backend
                           |
                +----------+----------+
                |                     |
                v                     v
          MySQL Database       Local AI Service
                                      |
                                      v
                              Llama 3.1 8B + LoRA
                                      |
                                      v
                                Evaluation JSON
                                      |
                                      v
                                  PHP Backend
                                      |
                                      v
                                Web Interface
```

The local AI service runs on:

`http://localhost:8000`

The AI evaluation endpoint is:

`http://localhost:8000/evaluate`

---

## Project Structure

```text
AI-Idea-Evaluation-System/
│
├── ai/
│   ├── server.py
│   ├── mock_server.py
│   ├── requirements.txt
│   ├── requirements-ai.txt
│   ├── README.md
│   └── .gitignore
│
├── backend/
│   ├── api/
│   │   ├── evaluate.php
│   │   ├── ideas.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── notifications.php
│   ├── config.php
│   ├── db.php
│   ├── .env.example
│   └── README.md
│
├── frontend/
│   ├── assets/
│   ├── database/
│   │   └── schema.sql
│   ├── includes/
│   ├── pages/
│   └── .env.example
│
├── .gitignore
└── README.md
```

# Requirements

## Web Application

The web application requires:

- XAMPP
- Apache
- MySQL
- PHP
- PHP cURL extension
- A modern web browser

## AI Service

The local AI service requires:

- Python 3.13 recommended
- NVIDIA GPU with CUDA support
- Sufficient GPU VRAM for local Llama 3.1 8B 4-bit inference
- PyTorch
- Unsloth
- PEFT
- bitsandbytes
- Accelerate
- FastAPI
- Uvicorn
- Pydantic

The exact PyTorch, CUDA, and Unsloth installation should be selected according to the NVIDIA GPU and operating system of the machine running the AI service.

For detailed AI information, see:

`ai/README.md`

# AI Model

The project uses a locally running fine-tuned Llama model.

## Base Model

`unsloth/Meta-Llama-3.1-8B-Instruct-bnb-4bit`

## Fine-Tuning Method

The model was fine-tuned using:

`LoRA`

## LoRA Adapter

`iamAmalMMA/llama3_1_8b_lora_ideas`

The Hugging Face repository contains the trained LoRA adapter and its configuration.

The adapter configuration specifies the base model:

`unsloth/Meta-Llama-3.1-8B-Instruct-bnb-4bit`

## Inference Configuration

The AI server uses:

- Maximum sequence length: 2048
- Model loading: 4-bit
- Inference framework: Unsloth
- API framework: FastAPI

The AI model is loaded locally when `server.py` starts.

# Important AI Deployment Note

The project is designed to run the AI locally rather than using a hosted AI inference API.

The Hugging Face LoRA adapter is downloaded when required, together with the base model specified in its configuration.

Therefore, internet access is required during the initial AI setup/model download.

After the required model files are available locally, the AI service runs locally on the machine.

A compatible NVIDIA GPU/CUDA environment is required for practical local inference with the 8B model.

The exact GPU and CUDA configuration should be verified before installing PyTorch, Unsloth, and bitsandbytes.

# 1. XAMPP Setup

Install XAMPP on the machine that will run the web application.

Start the following services from the XAMPP Control Panel:

- Apache
- MySQL

Place the project inside the XAMPP `htdocs` directory.

For example:

```text
C:\xampp\htdocs\AiProject\AI-Idea-Evaluation-System
```

The parent directory can be different on another machine.

If the project is placed under a different URL path, the backend URL in the frontend environment configuration may need to be updated.

# 2. Database Setup

The database schema is provided in:

```text
frontend/database/schema.sql
```

The SQL file creates the database:

```text
smart_ideas
```

and creates the required tables:

- `users`
- `ideas`
- `evaluations`
- `notifications`

The schema also contains demonstration accounts and sample data for testing the system.

## Using phpMyAdmin

1. Start MySQL from XAMPP.
2. Open phpMyAdmin.
3. Select **Import**.
4. Select:

   ```text
   frontend/database/schema.sql
   ```

5. Execute the import.
6. Confirm that the `smart_ideas` database was created.

# 3. Backend Configuration

The backend uses environment variables for its database and AI configuration.

Create:

```text
backend/.env
```

using:

```text
backend/.env.example
```

The configuration should contain:

```env
DB_DSN=mysql:host=127.0.0.1;dbname=smart_ideas;charset=utf8mb4
DB_USER=root
DB_PASSWORD=
AI_API_URL=http://localhost:8000/evaluate
```

If MySQL uses a password for the root account, update:

```env
DB_PASSWORD=
```

with the correct password.

The important AI setting is:

```env
AI_API_URL=http://localhost:8000/evaluate
```

This connects the PHP backend to the local AI service.

# 4. Frontend Configuration

Create:

```text
frontend/.env
```

using:

```text
frontend/.env.example
```

The configuration should contain:

```env
DB_DSN=mysql:host=127.0.0.1;dbname=smart_ideas;charset=utf8mb4
DB_USER=root
DB_PASSWORD=
AI_API_URL=http://localhost:8000/evaluate
BACKEND_API_URL=http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api
```

If the project is placed under a different XAMPP path, update:

```env
BACKEND_API_URL
```

to match the actual URL of the backend API.

For example, if the project is placed under:

```text
C:\xampp\htdocs\MyProject
```

the backend URL should be adjusted accordingly.

# 5. AI Environment Setup

The AI service is located in:

```text
ai/
```

The actual AI server is:

```text
ai/server.py
```

Open PowerShell or a terminal inside the `ai` directory.

Create a Python virtual environment:

```powershell
python -m venv .venv
```

Activate it on Windows:

```powershell
.\.venv\Scripts\Activate.ps1
```

The virtual environment is intentionally excluded from Git and should not be included in the project submission.

# 6. Install AI Dependencies

The project contains:

```text
ai/requirements-ai.txt
```

which lists the main dependencies required by the real AI service:

- `fastapi`
- `uvicorn`
- `pydantic`
- `torch`
- `unsloth`
- `peft`
- `bitsandbytes`
- `accelerate`

The dependency file is intended to document the Python packages used by the AI inference service.

## GPU/CUDA Compatibility

PyTorch, Unsloth, and bitsandbytes can require GPU/CUDA-specific installation configurations.

Therefore, before installing the complete AI environment, verify the NVIDIA GPU and CUDA compatibility of the machine that will run the AI.

Install the compatible PyTorch/Unsloth configuration for the target machine, then install the remaining Python dependencies.

# 7. Start the Local AI Service

After the AI environment has been configured, activate the virtual environment:

```powershell
.\.venv\Scripts\Activate.ps1
```

Then start the AI server:

```powershell
python server.py
```

The service runs on:

`http://localhost:8000`

Keep this terminal running while using the web application.

The AI service must be running before an idea can be evaluated.

# 8. Test the AI Health Endpoint

After starting `server.py`, open:

`http://localhost:8000/health`

The expected response is:

```json
{
  "status": "ok",
  "service": "Smart Idea Evaluation AI"
}
```

A successful response confirms that the FastAPI service is running.

# 9. Test the AI Evaluation Endpoint

The AI provides:

```text
POST /evaluate
```

Full endpoint:

`http://localhost:8000/evaluate`

The request format is:

```json
{
  "title": "Idea title",
  "description": "Idea description"
}
```

The AI returns a structured JSON evaluation containing:

```text
evaluation
├── scores
│   ├── innovation
│   ├── feasibility
│   ├── business_value
│   ├── sustainability
│   └── cost
│
├── overall_score
├── strengths
├── improvement_opportunities
└── improved_proposal
    ├── suggested_title
    └── suggested_description
```

This JSON structure is consumed by the PHP backend.

# 10. Start the Web Application

Before using the web application, make sure:

- Apache is running.
- MySQL is running.
- The `smart_ideas` database has been imported.
- `backend/.env` has been configured.
- `frontend/.env` has been configured.
- The local AI server is running on port 8000.

Then open the project through Apache.

For example:

`http://localhost/AiProject/AI-Idea-Evaluation-System/frontend/`

The exact URL depends on the location of the project inside `htdocs`.

# 11. Login

The system is intended for internal organizational use.

There is no public sign-up page.

Users log in using accounts configured in the database.

The demonstration accounts are included in:

```text
frontend/database/schema.sql
```

After importing the database, the available demonstration accounts can be used to access the system.

# 12. End-to-End AI Evaluation Flow

When an idea is submitted for evaluation, the process is:

```text
User
 |
 v
Web Interface
 |
 v
PHP Backend
 |
 v
backend/api/ideas.php
 |
 v
backend/api/evaluate.php
 |
 v
http://localhost:8000/evaluate
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
 +----> MySQL Database
 |
 v
Web Interface
 |
 v
Evaluation Results
```

The AI inference happens locally.

The PHP backend does not rely on an external hosted AI inference API during normal operation.

# 13. AI Mock Server

The project also contains:

```text
ai/mock_server.py
```

The mock server is only intended for development and integration testing.

It does not load the trained Llama model.

It can be used to test the connection between the PHP backend and the AI API when the full AI environment is unavailable.

To start the mock server:

```powershell
python mock_server.py
```

It uses:

`http://localhost:8000`

Do not run the mock server and the real AI server at the same time because both use port 8000.

For the final system demonstration, use:

```text
server.py
```

because it loads the actual trained AI model.

# 14. Environment Files and GitHub

The repository contains example configuration files:

```text
backend/.env.example
frontend/.env.example
```

Actual `.env` files are intentionally excluded from Git.

To configure a new installation:

```text
backend/.env.example
        ↓
backend/.env
```

and:

```text
frontend/.env.example
        ↓
frontend/.env
```

Update the values according to the local machine.

Do not commit passwords or other sensitive configuration values to GitHub.

# 15. Git Repository

The project source code is maintained in Git.

The repository contains:

- Frontend source code
- Backend source code
- AI source code
- AI dependency files
- Database schema
- Environment templates
- Documentation

The local Python virtual environment is not included in the repository.

Development backup files are also excluded from the repository.

# 16. Troubleshooting

## AI service does not start

Check:

- Python version
- NVIDIA GPU availability
- CUDA compatibility
- PyTorch installation
- Unsloth installation
- bitsandbytes installation
- Internet connectivity
- Available system and GPU memory

Then run:

```powershell
python server.py
```

and check the terminal for the startup error.

## AI health endpoint does not respond

Open:

`http://localhost:8000/health`

If it does not respond:

- Make sure `server.py` is running.
- Make sure port 8000 is available.
- Check the terminal running the AI server for errors.

## PHP cannot connect to the AI

Check:

```text
backend/.env
```

and verify:

```env
AI_API_URL=http://localhost:8000/evaluate
```

Also verify that the AI server is running.

## Database connection fails

Check:

- MySQL is running in XAMPP.
- The `smart_ideas` database exists.
- `frontend/database/schema.sql` was imported.
- `DB_DSN` is correct.
- `DB_USER` is correct.
- `DB_PASSWORD` is correct.

## Website cannot connect to the backend

Check:

```text
frontend/.env
```

and verify:

```env
BACKEND_API_URL
```

matches the actual URL of the backend API.

For example:

```text
http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api
```

## Evaluation does not work

Check the following in order:

1. **AI server**

   Open:

   `http://localhost:8000/health`

2. **Backend AI URL**

   Check:

   ```text
   backend/.env
   ```

   and verify:

   ```env
   AI_API_URL=http://localhost:8000/evaluate
   ```

3. **Apache**

   Confirm Apache is running in XAMPP.

4. **MySQL**

   Confirm MySQL is running.

5. **Database**

   Confirm the `smart_ideas` database exists.

6. **Browser**

   Refresh the web application and submit the idea again.

# 17. Local Deployment Summary

For a complete local deployment, the startup order is:

```text
1. Start XAMPP
   |
   +--> Apache
   |
   +--> MySQL

2. Make sure the smart_ideas database is imported

3. Start the AI virtual environment

4. Start:
   python server.py

5. Verify:
   http://localhost:8000/health

6. Open the web application through Apache

7. Log in

8. Submit an idea for evaluation
```

# 18. Important Files

| File | Purpose |
|---|---|
| `ai/server.py` | Actual local AI inference API |
| `ai/mock_server.py` | Lightweight AI mock server for integration testing |
| `ai/requirements-ai.txt` | Dependencies for the real AI service |
| `ai/README.md` | Detailed AI setup information |
| `backend/api/evaluate.php` | Connects the PHP backend to the local AI |
| `backend/api/ideas.php` | Handles idea operations and evaluation flow |
| `backend/.env.example` | Backend configuration template |
| `frontend/.env.example` | Frontend configuration template |
| `frontend/database/schema.sql` | Database creation and sample data |
| `.gitignore` | Prevents local and temporary files from being committed |

# 19. Local AI Design

The AI component was designed to run locally as part of the project.

The system uses:

```text
Llama 3.1 8B Instruct
        +
LoRA Fine-Tuning
        +
4-bit Inference
        +
Unsloth
        +
FastAPI
```

The trained LoRA adapter is hosted on Hugging Face:

`iamAmalMMA/llama3_1_8b_lora_ideas`

The adapter configuration specifies:

`unsloth/Meta-Llama-3.1-8B-Instruct-bnb-4bit`

as its base model.

The web application communicates with the AI through the local FastAPI endpoint:

`http://localhost:8000/evaluate`

No external AI inference service is required for normal operation.

# 20. Training and AI Source Materials

The project was developed using a fine-tuning workflow based on Llama 3.1 8B Instruct and LoRA.

The training notebook and related AI source materials may be provided separately as part of the technical project documentation.

The deployed application uses the trained LoRA adapter for inference and does not need to retrain the model during normal operation.

# 21. Final Deployment Goal

The project is intended to be provided as a complete technical package containing:

- Web application source code
- Backend source code
- Local AI source code
- Trained AI adapter reference
- Database schema
- Environment templates
- Dependency files
- Setup documentation

After installing the required software and configuring the local environment, the complete system can be run locally with:

```text
XAMPP
  +
MySQL
  +
Local FastAPI AI Service
  +
Web Browser
```

The AI evaluation remains local to the deployment machine.
