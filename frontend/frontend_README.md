# Smart Idea Evaluation System — Frontend

## Overview

This folder contains the PHP-based web interface of the **Smart Idea Evaluation System**.

The frontend provides the user-facing pages for submitting, viewing, managing, and evaluating ideas. It is designed for an internal organizational environment and uses an Arabic right-to-left (RTL) interface.

The frontend communicates with the PHP backend through the application's API endpoints. The backend is responsible for database operations, authentication, business logic, and communication with the local AI service.

The frontend does not perform AI inference directly.

---

# Frontend Architecture

The frontend is part of the complete system architecture:

```text
User
 |
 v
Frontend Web Interface
(PHP / HTML / CSS / JavaScript)
 |
 v
PHP Backend APIs
 |
 +--------------------+
 |                    |
 v                    v
MySQL Database    Local AI Service
                       |
                       v
                Llama 3.1 8B
                + LoRA Adapter
```

The main responsibilities are divided as follows:

| Component | Responsibility |
|---|---|
| Frontend | User interface, page rendering, forms, navigation, and presentation |
| Backend | Authentication, business logic, API processing, database operations, and AI integration |
| Database | Persistent storage for users, ideas, evaluations, and notifications |
| Local AI | Evaluation of submitted ideas and generation of structured evaluation results |

---

# Project Structure

```text
frontend/
├── assets/
│   ├── css/
│   │   ├── global.css
│   │   └── php-extra.css
│   ├── images/
│   │   ├── jeddah-logo.png
│   │   └── jeddah-logo-white.png
│   └── js/
│       └── app.js
├── data/
│   ├── ideas.json
│   ├── notifications.json
│   ├── seed-ideas.json
│   └── seed-notifications.json
├── database/
│   ├── schema.sql
│   └── README-DATABASE.md
├── docs/
│   ├── AI-CONTRACT.md
│   ├── BACKEND-HANDOFF.md
│   └── INTEGRATION-CHECKLIST.md
├── includes/
│   ├── bootstrap.php
│   ├── db.php
│   ├── functions.php
│   ├── header.php
│   ├── idea_table.php
│   ├── layout_bottom.php
│   └── layout_top.php
├── pages/
│   ├── analytics.php
│   ├── dashboard.php
│   ├── details.php
│   ├── ideas.php
│   ├── new.php
│   ├── notifications.php
│   └── top.php
├── analytics.php
├── dashboard.php
├── idea_details.php
├── ideas.php
├── index.php
├── login.php
├── new_idea.php
├── notifications.php
├── README-DATABASE.md
├── README.md
└── top_ideas.php
```

---

# Main Frontend Components

## Main PHP Pages

The root PHP files provide the main entry points for the web application.

| File | Purpose |
|---|---|
| `index.php` | Main application entry point |
| `login.php` | Login page |
| `dashboard.php` | Dashboard interface |
| `ideas.php` | Ideas listing |
| `new_idea.php` | New idea submission interface |
| `idea_details.php` | Idea details and evaluation information |
| `notifications.php` | Notifications interface |
| `analytics.php` | Analytics interface |
| `top_ideas.php` | Top ideas interface |

The `pages/` directory contains the corresponding page implementations used by the application's shared frontend structure.

---

# Shared Includes

The `includes/` directory contains reusable PHP components.

### `bootstrap.php`

Provides common initialization and application setup used by the frontend.

### `db.php`

Provides frontend-side database functionality used where required by the application.

### `functions.php`

Contains shared application helper functions.

### `header.php`

Contains shared page header and navigation elements.

### `idea_table.php`

Provides reusable idea-table rendering.

### `layout_top.php`

Provides shared layout initialization.

### `layout_bottom.php`

Provides shared layout closing elements.

---

# Assets

## CSS

The frontend uses:

```text
assets/css/global.css
assets/css/php-extra.css
```

These files contain the main visual styling used by the application.

The interface is designed for Arabic RTL presentation.

---

## JavaScript

The main JavaScript file is:

```text
assets/js/app.js
```

It contains client-side functionality used by the web interface, including frontend interactions and communication required by the application.

---

## Images

The `assets/images/` directory contains the Jeddah Municipality branding assets used by the interface.

```text
assets/images/
├── jeddah-logo.png
└── jeddah-logo-white.png
```

---

# Authentication

The system is intended for internal organizational use.

There is no public sign-up page.

Users log in using accounts that are already configured in the system database.

The frontend provides the login interface, while authentication and session handling are coordinated with the PHP backend.

---

# Idea Workflow

The frontend supports the idea lifecycle used by the system.

The general workflow is:

```text
Draft
  |
  v
Submitted / Processing
  |
  v
Evaluated
  |
  +------------+
  |            |
  v            v
Approved     Rejected
```

An idea can be saved as a draft before it is submitted for processing.

Once an idea is submitted for evaluation, the backend sends it to the local AI service.

After the AI evaluation is completed, the resulting evaluation information is displayed through the frontend.

---

# AI Integration

The frontend does **not** run the AI model directly.

The communication flow is:

```text
Frontend
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
Llama 3.1 8B + LoRA
   |
   v
Evaluation JSON
   |
   v
PHP Backend
   |
   v
Frontend
```

The local AI service runs by default at:

```text
http://localhost:8000
```

The evaluation endpoint is:

```text
http://localhost:8000/evaluate
```

The AI service setup is documented in:

```text
../ai/README.md
```

---

# AI Evaluation Results

The frontend displays evaluation information returned through the backend.

The AI evaluation includes:

- Innovation score
- Feasibility score
- Business Value score
- Sustainability score
- Cost score
- Overall score
- Strengths
- Improvement opportunities
- Improved idea title
- Improved idea description

Each criterion is scored on a scale of 0 to 5.

The frontend is responsible for presenting these results. The AI model is responsible for generating them, while the backend processes and stores the relevant information.

---

# Top Ideas

The frontend includes a Top Ideas section.

The Top 5 ranking is calculated by application/backend logic from eligible evaluated ideas and their final AI scores.

The Top 5 ranking does not by itself determine whether an idea can be nominated for implementation.

Administrative decisions such as approval or rejection are handled by the application/backend logic and reflected in the frontend.

---

# Analytics

The analytics pages present application-level information and statistics.

Analytics are calculated by application/backend logic.

The AI service does not calculate the application's analytics.

The frontend displays the resulting analytics through the appropriate dashboard and analytics pages.

---

# Notifications

The frontend includes a notifications interface.

Notifications are generated and managed through the backend and stored in the database.

The frontend retrieves and displays the notifications to the appropriate users.

The main notification page is:

```text
notifications.php
```

and the corresponding page implementation is located in:

```text
pages/notifications.php
```

---

# Backend API Connection

The frontend communicates with the PHP backend using the backend API.

The default local backend API URL is:

```text
http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api
```

The frontend environment template contains:

```text
BACKEND_API_URL=http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api
```

The exact URL depends on where the project is placed inside the XAMPP `htdocs` directory.

If the project folder is renamed or moved, update the local environment configuration accordingly.

---

# Environment Configuration

The frontend provides:

```text
frontend/.env.example
```

The actual `.env` file is intentionally excluded from source control.

Create:

```text
frontend/.env
```

on the deployment machine based on the example file.

The example contains configuration values for:

```text
DB_DSN=mysql:host=127.0.0.1;dbname=smart_ideas;charset=utf8mb4
DB_USER=root
DB_PASSWORD=
AI_API_URL=http://localhost:8000/evaluate
BACKEND_API_URL=http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api
```

The database and AI settings should match the local deployment environment.

---

# Database

The database schema is located at:

```text
database/schema.sql
```

The schema creates the `smart_ideas` database and the tables required by the application.

The database documentation is available at:

```text
database/README-DATABASE.md
```

The frontend depends on the backend/database configuration for persistent application data.

---

# Database-Backed Application

The final application uses the PHP backend and MySQL database for the main system workflow.

The `data/` directory contains JSON files that were used by the frontend prototype and seed/demo workflow.

```text
data/
├── ideas.json
├── notifications.json
├── seed-ideas.json
└── seed-notifications.json
```

These files are included as project resources, but the production application workflow uses the backend and MySQL database rather than relying on JSON files as the primary persistent data store.

---

# Documentation

The `docs/` directory contains integration documentation.

## `AI-CONTRACT.md`

Documents the expected AI input/output contract between the application and the AI service.

## `BACKEND-HANDOFF.md`

Documents backend integration and workflow requirements.

## `INTEGRATION-CHECKLIST.md`

Provides an integration checklist for connecting the frontend, backend, database, and AI service.

These documents provide additional technical context for developers working on the project.

---

# Local Setup

## Requirements

The frontend requires:

- XAMPP
- Apache
- PHP
- MySQL / MariaDB
- A web browser
- The project backend
- The local AI service for AI evaluation functionality

The AI-specific requirements are documented separately in:

```text
../ai/README.md
```

---

## 1. Install XAMPP

Install XAMPP on the deployment machine.

Make sure Apache and MySQL are available.

---

## 2. Place the Project in `htdocs`

Place the complete project inside the XAMPP `htdocs` directory.

For example:

```text
C:\xampp\htdocs\AiProject\AI-Idea-Evaluation-System
```

The project should retain its `frontend`, `backend`, and `ai` directories.

---

## 3. Start Apache and MySQL

Open the XAMPP Control Panel.

Start:

```text
Apache
MySQL
```

---

## 4. Configure the Database

Import:

```text
database/schema.sql
```

into MySQL.

For detailed instructions, see:

```text
database/README-DATABASE.md
```

---

## 5. Configure the Backend

Create:

```text
../backend/.env
```

using:

```text
../backend/.env.example
```

The backend must be configured to connect to the database and local AI service.

See:

```text
../backend/README.md
```

for backend configuration details.

---

## 6. Configure the Frontend

Create:

```text
frontend/.env
```

using:

```text
frontend/.env.example
```

Update `BACKEND_API_URL` if the project is placed under a different XAMPP path.

---

## 7. Start the Local AI Service

For AI-powered evaluation, start the local FastAPI service.

From the project root:

```powershell
cd ai
```

Then follow the AI setup instructions in:

```text
ai/README.md
```

The default AI evaluation endpoint is:

```text
http://localhost:8000/evaluate
```

---

## 8. Open the Web Application

With Apache and MySQL running, open the project through the local Apache server.

For the default project location:

```text
http://localhost/AiProject/AI-Idea-Evaluation-System/
```

The exact URL depends on the project directory name and location inside `htdocs`.

---

# Complete Startup Order

For the full system, use the following startup order:

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

The resulting architecture is:

```text
Browser
   |
   v
Apache / PHP
   |
   +--------------------+
   |                    |
   v                    v
MySQL              Local AI
                    :8000
```

---

# Testing the Frontend

After starting the system, verify:

1. The login page opens.
2. An existing user can log in.
3. The dashboard loads correctly.
4. Ideas can be viewed.
5. A new idea can be created.
6. An idea can be saved as a draft.
7. A submitted idea can be sent for evaluation.
8. The AI evaluation result is displayed.
9. Evaluation details are visible on the idea details page.
10. Notifications are displayed.
11. Analytics pages load.
12. Top Ideas displays the appropriate ranking.
13. Administrative actions are reflected in the interface.

---

# Troubleshooting

## The Website Does Not Open

Check:

- Apache is running in XAMPP.
- The project is inside the XAMPP `htdocs` directory.
- The URL matches the project directory name.
- PHP files are being served through Apache rather than opened directly from the filesystem.

---

## Backend API Requests Fail

Check:

```text
frontend/.env
```

and verify:

```text
BACKEND_API_URL=http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api
```

Make sure the URL matches the actual project location.

Also verify that Apache is running.

---

## Login Does Not Work

Check:

- MySQL is running.
- The `smart_ideas` database exists.
- The users table contains the required user accounts.
- The backend `.env` configuration is correct.
- Apache/PHP is running correctly.

---

## Ideas Cannot Be Submitted

Check:

1. The user is logged in.
2. The backend API is accessible.
3. MySQL is running.
4. The database connection is working.
5. The local AI service is running if the idea is being submitted for evaluation.
6. `AI_API_URL` points to the correct local AI endpoint.

---

## AI Evaluation Does Not Appear

First check the local AI health endpoint:

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

Then verify the backend configuration:

```text
AI_API_URL=http://localhost:8000/evaluate
```

The AI service must be running before an idea can be evaluated.

---

# Frontend and Backend Relationship

The frontend and backend are separate project components but work together as one application.

```text
frontend/
     |
     | HTTP/API requests
     v
backend/api/
     |
     +------------------+
     |                  |
     v                  v
MySQL              Local AI
```

The frontend handles presentation and user interaction.

The backend handles application logic and integration.

The database stores persistent system data.

The AI service performs idea evaluation.

---

# Important Files

| File / Directory | Purpose |
|---|---|
| `index.php` | Main application entry point |
| `login.php` | Login interface |
| `dashboard.php` | Dashboard |
| `ideas.php` | Ideas listing |
| `new_idea.php` | New idea submission |
| `idea_details.php` | Idea details and evaluation |
| `notifications.php` | Notifications |
| `analytics.php` | Analytics |
| `top_ideas.php` | Top ideas |
| `includes/` | Shared PHP components |
| `assets/css/` | Stylesheets |
| `assets/js/` | JavaScript |
| `assets/images/` | Images and branding |
| `database/schema.sql` | Database schema |
| `docs/` | Integration documentation |
| `.env.example` | Frontend environment template |

---

# Related Documentation

For the complete project:

### Root Project Documentation

```text
../README.md
```

Contains the overall system architecture, installation, deployment, and project information.

### Backend Documentation

```text
../backend/README.md
```

Contains PHP backend setup, APIs, database integration, and AI integration details.

### AI Documentation

```text
../ai/README.md
```

Contains local AI model requirements, installation, model configuration, and AI service instructions.

### Database Documentation

```text
database/README-DATABASE.md
```

Contains database schema and database setup information.

---

# Final Frontend Role

The frontend provides the user-facing interface for the Smart Idea Evaluation System.

It connects the users to the application's backend and presents:

- Authentication
- Dashboard information
- Idea submission
- Draft management
- Idea evaluation results
- Idea details
- Notifications
- Analytics
- Top ideas
- Administrative decisions

The frontend does not contain or execute the Llama model.

The complete AI-powered evaluation workflow is performed through the PHP backend and the local AI service.
