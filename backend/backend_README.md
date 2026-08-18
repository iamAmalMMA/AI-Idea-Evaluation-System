# Smart Idea Evaluation System — Backend

## Overview

The `backend` folder contains the PHP backend of the **Smart Idea Evaluation System**.

The backend is responsible for:

- Connecting the application to the MySQL database.
- Managing authenticated user sessions.
- Providing API endpoints used by the web application.
- Creating and managing ideas.
- Sending submitted ideas to the local AI evaluation service.
- Processing AI evaluation results.
- Storing evaluation results in the database.
- Creating notifications for administrators.
- Returning JSON responses to the frontend.

The backend runs through **PHP and Apache**, normally provided by **XAMPP**.

---

## Backend Architecture

The backend sits between the web interface, database, and local AI service.

```text
Web Interface
      |
      | HTTP requests
      v
PHP Backend
      |
      +--------------------+
      |                    |
      v                    v
MySQL Database       Local AI Service
                           |
                           v
                    Llama 3.1 8B
                    + LoRA Adapter
```

The backend is responsible for coordinating these components. The AI model itself is located in the separate `ai` folder.

---

# Project Structure

```text
backend/
├── api/
│   ├── evaluate.php
│   ├── ideas.php
│   ├── login.php
│   ├── logout.php
│   └── notifications.php
├── config.php
├── db.php
├── .env.example
├── .gitignore
└── README.md
```

## `config.php`

Contains backend configuration and environment-value handling used by the PHP application.

The backend reads environment values such as the database connection settings and AI service URL from the local `.env` file.

---

## `db.php`

Contains the database connection functionality used by the backend.

The application connects to the MySQL database configured in the backend environment.

---

## `api/login.php`

Handles user login and authentication.

The system is intended for internal organizational use, so users do not register through the website.

Users log in using accounts that are already configured in the database.

---

## `api/logout.php`

Handles user logout and session termination.

---

## `api/ideas.php`

Provides the main idea-management API.

It supports retrieving ideas and processing idea submissions.

For submitted ideas, the API:

1. Verifies that the user is authenticated.
2. Reads the submitted idea data.
3. Creates or updates the idea.
4. Saves drafts when the request is submitted as a draft.
5. Sends submitted ideas to the AI evaluation endpoint.
6. Processes the returned evaluation.
7. Stores the evaluation in the database.
8. Updates the idea status and score.
9. Creates an administrator notification.
10. Returns the evaluation result to the web application.

---

## `api/evaluate.php`

Provides the backend endpoint used to communicate with the local AI service.

It:

1. Accepts an idea title and description.
2. Reads `AI_API_URL` from the backend environment.
3. Sends the idea to the local FastAPI AI service using an HTTP POST request.
4. Receives the AI response.
5. Returns the AI response to the caller.

The endpoint expects JSON input:

```json
{
  "title": "Idea title",
  "description": "Idea description"
}
```

The AI service is expected to return a structured JSON evaluation.

---

## `api/notifications.php`

Handles notification-related API functionality used by the application.

Notifications are stored in the database and can be retrieved by the web application.

The idea submission workflow also creates an administrator notification after an idea has been successfully evaluated.

---

# Environment Configuration

The backend uses a local `.env` file for environment-specific configuration.

The actual `.env` file is intentionally **not included in Git** or the project submission.

Instead, the project provides:

```text
backend/.env.example
```

Create:

```text
backend/.env
```

on the deployment machine based on the example file.

The current example configuration is:

```text
DB_DSN=mysql:host=127.0.0.1;dbname=smart_ideas;charset=utf8mb4
DB_USER=root
DB_PASSWORD=
AI_API_URL=http://localhost:8000/evaluate
```

## Configuration Values

| Variable | Purpose |
|---|---|
| `DB_DSN` | MySQL database connection string |
| `DB_USER` | MySQL username |
| `DB_PASSWORD` | MySQL password |
| `AI_API_URL` | URL of the local AI evaluation service |

The default local configuration assumes:

- MySQL is running on `127.0.0.1`.
- The database is named `smart_ideas`.
- The MySQL user is `root`.
- The local MySQL password is empty unless configured otherwise.
- The AI service runs on port `8000`.

If the organization's MySQL configuration is different, update the local `.env` file accordingly.

---

# Database

The backend uses MySQL.

The database schema is included in:

```text
frontend/database/schema.sql
```

The schema creates the:

```text
smart_ideas
```

database and the tables required by the application.

The backend expects the database to be available before the application is used.

For database installation instructions, see:

```text
frontend/database/README-DATABASE.md
```

---

# API Endpoints

The backend API endpoints are located under:

```text
backend/api/
```

The main endpoints are:

| Endpoint | Purpose |
|---|---|
| `login.php` | User authentication |
| `logout.php` | User logout |
| `ideas.php` | Retrieve and manage ideas |
| `evaluate.php` | Send an idea to the local AI service |
| `notifications.php` | Manage application notifications |

The frontend communicates with these PHP endpoints through HTTP requests.

---

# Idea Evaluation Workflow

The complete evaluation process is coordinated by the backend.

```text
User
 |
 v
Web Interface
 |
 v
ideas.php
 |
 v
evaluate.php
 |
 v
Local FastAPI AI
 |
 v
Llama 3.1 8B + LoRA
 |
 v
Evaluation JSON
 |
 v
evaluate.php
 |
 v
ideas.php
 |
 +----------------------+
 |                      |
 v                      v
MySQL Database      Admin Notification
 |
 v
Web Interface
```

---

## Idea Submission

When a user submits an idea for processing, the backend creates the idea with a processing status.

The idea contains information such as:

- Idea number
- User ID
- Title
- Description
- Department
- Category
- Status
- Score

The backend generates an idea number in the format:

```text
IDEA-YYYY-XXXX
```

where the year corresponds to the current year and the numeric portion is generated from the database ID sequence.

---

## Draft Ideas

The backend supports saving an idea as a draft.

When the request is submitted as a draft:

```text
submit_type = draft
```

the idea is saved with:

```text
status = draft
```

The AI evaluation is not performed for a draft.

---

## Evaluated Ideas

When an idea is submitted for processing rather than saved as a draft, the backend sends the title and description to the AI service.

After a successful evaluation, the idea status is updated to:

```text
evaluated
```

and the overall AI score is stored with the idea.

---

# AI Integration

The backend connects to the local AI service through:

```text
AI_API_URL=http://localhost:8000/evaluate
```

The local AI service is implemented in:

```text
ai/server.py
```

The backend sends:

```json
{
  "title": "Idea title",
  "description": "Idea description"
}
```

The AI service returns structured evaluation data.

The backend then stores the relevant evaluation information in the database.

---

## Evaluation Data

The evaluation includes the following scoring dimensions:

- Innovation
- Feasibility
- Sustainability
- Cost
- Business value

The backend also processes:

- Overall score
- Strengths
- Improvement opportunities
- Improved title
- Improved description

The resulting information can then be displayed through the web interface.

---

# Error Handling

The backend validates incoming requests and returns HTTP status codes with JSON responses.

Examples include:

### Invalid request

```text
400 Bad Request
```

Used when the submitted request data is invalid.

### Authentication required

```text
401 Unauthorized
```

Used when an authenticated session is required but the user is not logged in.

### Unsupported HTTP method

```text
405 Method Not Allowed
```

Used when an endpoint receives an unsupported request method.

### Server or AI evaluation error

```text
500 Internal Server Error
```

Used when an unexpected backend error occurs or the AI evaluation cannot be completed.

---

# Local Deployment

The backend is designed to run through Apache using XAMPP.

## Requirements

The deployment machine should have:

- Windows or another supported operating system
- XAMPP
- Apache
- PHP
- MySQL / MariaDB
- PHP cURL support
- The Smart Idea Evaluation System project files
- The local AI environment and dependencies

The AI component has additional requirements documented in:

```text
ai/README.md
```

---

# Installation

## 1. Install XAMPP

Install XAMPP on the deployment machine and make sure Apache and MySQL are available.

---

## 2. Place the Project in `htdocs`

Place the project under the XAMPP web directory.

For example:

```text
C:\xampp\htdocs\AiProject\AI-Idea-Evaluation-System
```

The exact folder name can be changed if the corresponding frontend configuration is updated.

---

## 3. Start Apache and MySQL

Open the XAMPP Control Panel.

Start:

```text
Apache
MySQL
```

Both services should be running before using the web application.

---

## 4. Create the Backend Environment File

Copy:

```text
backend/.env.example
```

to:

```text
backend/.env
```

Then update the values according to the deployment environment.

For the default local setup:

```text
DB_DSN=mysql:host=127.0.0.1;dbname=smart_ideas;charset=utf8mb4
DB_USER=root
DB_PASSWORD=
AI_API_URL=http://localhost:8000/evaluate
```

---

## 5. Configure the Database

Import:

```text
frontend/database/schema.sql
```

into MySQL.

This creates the required database and tables.

See:

```text
frontend/database/README-DATABASE.md
```

for database-specific documentation.

---

## 6. Start the Local AI Service

The AI service must be running before submitting an idea for AI evaluation.

From the project root:

```powershell
cd ai
```

Activate the AI environment and start the server according to:

```text
ai/README.md
```

The default AI endpoint is:

```text
http://localhost:8000/evaluate
```

---

## 7. Open the Web Application

With Apache and MySQL running, open the project through the local Apache server.

The default project URL is:

```text
http://localhost/AiProject/AI-Idea-Evaluation-System/
```

The exact URL depends on the project location inside the XAMPP `htdocs` directory.

---

# Running the Complete System

The normal startup order is:

```text
1. Start XAMPP
       |
       v
2. Start Apache
       |
       v
3. Start MySQL
       |
       v
4. Start the local AI service
       |
       v
5. Open the web application
```

The services communicate locally:

```text
Browser
   |
   v
Apache / PHP
   |
   +------------------+
   |                  |
   v                  v
MySQL            Local AI
                 localhost:8000
```

---

# Testing the Backend AI Connection

The AI backend endpoint can be tested with a JSON POST request.

For example, using PowerShell:

```powershell
$body = @{
    title = "Smart Air Quality Monitoring System"
    description = "A platform that displays air quality indicators and sends alerts when pollution levels increase."
} | ConvertTo-Json

$response = Invoke-RestMethod `
    -Uri "http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api/evaluate.php" `
    -Method Post `
    -ContentType "application/json; charset=utf-8" `
    -Body $body

$response | ConvertTo-Json -Depth 10
```

A successful response should contain the submitted title and description together with the AI evaluation.

---

# Backend and AI Service Relationship

There are two separate HTTP endpoints involved in the evaluation process.

### Backend endpoint

```text
http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api/evaluate.php
```

### Local AI endpoint

```text
http://localhost:8000/evaluate
```

The browser/application communicates with the PHP backend.

The PHP backend communicates with the local AI service.

The browser does not need to communicate directly with the AI model.

```text
Browser
   |
   v
PHP evaluate.php
   |
   v
FastAPI /evaluate
   |
   v
Llama + LoRA
```

---

# Security and Environment Notes

The backend `.env` file may contain environment-specific credentials and is intentionally excluded from source control.

Only the following template should be shared through Git:

```text
backend/.env.example
```

The deployment team should create the actual `.env` file locally.

The project does not require users to create accounts through the web interface. User accounts are configured in the database and accessed through the login system.

---

# Troubleshooting

## Apache Is Not Running

If the website cannot be opened, verify that Apache is running in the XAMPP Control Panel.

---

## MySQL Is Not Running

If database operations fail, verify that MySQL is running in XAMPP.

Also verify the database name and credentials in:

```text
backend/.env
```

---

## Database Connection Error

Check:

```text
DB_DSN
DB_USER
DB_PASSWORD
```

in:

```text
backend/.env
```

Also verify that the `smart_ideas` database exists.

---

## AI Evaluation Fails

First verify that the local AI service is running.

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

Then verify:

```text
AI_API_URL=http://localhost:8000/evaluate
```

in:

```text
backend/.env
```

---

## Port 8000 Is Already in Use

The local AI service uses port `8000` by default.

Make sure another application is not already using this port.

If the AI service port is changed, update `AI_API_URL` accordingly.

---

# Relationship to Other Project Folders

The backend works together with the other project components:

```text
AI-Idea-Evaluation-System/
│
├── ai/
│   └── Local AI inference service
│
├── backend/
│   └── PHP backend and APIs
│
└── frontend/
    └── Web interface, database schema, and frontend resources
```

### `ai/`

Contains the local Llama-based AI inference service.

See:

```text
ai/README.md
```

### `frontend/`

Contains the web interface and frontend resources used by the application.

See:

```text
frontend/README.md
```

### `frontend/database/`

Contains the database schema and database documentation.

See:

```text
frontend/database/README-DATABASE.md
```

---

# Important Files

| File | Purpose |
|---|---|
| `config.php` | Backend configuration and environment handling |
| `db.php` | Database connection functionality |
| `api/login.php` | User authentication |
| `api/logout.php` | User logout |
| `api/ideas.php` | Idea management and evaluation workflow |
| `api/evaluate.php` | Communication with the local AI service |
| `api/notifications.php` | Notification API |
| `.env.example` | Environment configuration template |
| `README.md` | Backend documentation |

---

# Final Deployment Architecture

The complete deployed system consists of:

```text
                    User
                     |
                     v
              Web Browser
                     |
                     v
             Apache / PHP
                     |
          +----------+----------+
          |                     |
          v                     v
    MySQL Database        PHP Backend APIs
                                |
                                v
                       Local AI Service
                        FastAPI :8000
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
                      Database / Website
```

The AI inference remains local to the organization's deployment machine.

The backend acts as the integration layer between the web application, database, and local AI service.
