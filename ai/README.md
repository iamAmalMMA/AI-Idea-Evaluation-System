# Smart Idea Evaluation AI

## Overview

This folder contains the local AI inference service used by the Smart Idea Evaluation System.

The AI model evaluates submitted ideas and returns a structured JSON response that is consumed by the PHP backend and displayed through the web interface.

## Model

- Base model: unsloth/Meta-Llama-3.1-8B-Instruct-bnb-4bit
- Fine-tuning method: LoRA
- LoRA adapter: iamAmalMMA/llama3_1_8b_lora_ideas
- Maximum sequence length: 2048
- Model loading: 4-bit
- Inference framework: Unsloth
- API framework: FastAPI

## Project Structure

ai/
├── server.py
├── mock_server.py
├── requirements.txt
├── requirements-ai.txt
├── README.md
└── .gitignore

## Real AI Requirements

The real AI inference service uses Python, PyTorch, Unsloth, PEFT, bitsandbytes, Accelerate, FastAPI, Uvicorn, and Pydantic.

See requirements-ai.txt.

## Hardware

The AI model is Llama 3.1 8B running in 4-bit mode with a LoRA adapter.

A compatible GPU environment is recommended for practical local inference.

## Installation

Create a Python virtual environment:

python -m venv .venv

Activate it on Windows:

.\.venv\Scripts\Activate.ps1

Install the AI dependencies:

pip install -r requirements-ai.txt

## Start the AI Server

Run:

python server.py

The API starts on:

http://localhost:8000

## Health Check

Open:

http://localhost:8000/health

Expected response:

{
  "status": "ok",
  "service": "Smart Idea Evaluation AI"
}

## Evaluation Endpoint

The AI exposes:

POST /evaluate

Expected request:

{
  "title": "Idea title",
  "description": "Idea description"
}

The service returns the structured evaluation JSON consumed by the PHP backend.

## Connection to the Web Application

The PHP backend connects to the AI service using:

AI_API_URL=http://localhost:8000/evaluate

The complete flow is:

Web Interface
      ↓
PHP Backend
      ↓
backend/api/evaluate.php
      ↓
Local FastAPI AI Service
      ↓
Llama 3.1 8B + LoRA
      ↓
Evaluation JSON
      ↓
PHP Backend
      ↓
Database / Web Interface

## Mock Server

mock_server.py is included as a lightweight development/testing server.

It does not run the trained Llama model. It is intended only for testing the web application's integration when the full AI runtime is unavailable.

The actual project AI service is server.py.

## Model Access

The AI server uses the Hugging Face model repository configured in server.py.

The first model load may require downloading the base model and LoRA adapter and therefore requires internet access and sufficient local storage.
