import json
import torch

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from unsloth import FastLanguageModel


# ============================================================
# Configuration
# ============================================================

MODEL_NAME = "iamAmalMMA/llama3_1_8b_lora_ideas"
MAX_SEQ_LENGTH = 2048


# ============================================================
# FastAPI
# ============================================================

app = FastAPI(
    title="Smart Idea Evaluation AI",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ============================================================
# Request model
# ============================================================

class IdeaRequest(BaseModel):
    title: str
    description: str


# ============================================================
# Load model
# ============================================================

print("Loading AI model...")

model, tokenizer = FastLanguageModel.from_pretrained(
    model_name=MODEL_NAME,
    max_seq_length=MAX_SEQ_LENGTH,
    load_in_4bit=True,
)

FastLanguageModel.for_inference(model)

print("AI model loaded successfully.")


# ============================================================
# Evaluation endpoint
# ============================================================

@app.post("/evaluate")
async def evaluate_idea(payload: IdeaRequest):

    title = payload.title.strip()
    description = payload.description.strip()

    if not title and not description:
        raise HTTPException(
            status_code=400,
            detail="Please provide an idea title or description."
        )

    full_text = (
        f"العنوان: {title}\n"
        f"الوصف: {description}"
    )

    messages = [
        {
            "role": "system",
            "content": (
                "أنت نظام تقييم أفكار يرجع كائن JSON صالح ومكتمل فقط."
            )
        },
        {
            "role": "user",
            "content": (
                f"قم بتقييم الفكرة التالية:\n{full_text}"
            )
        }
    ]

    try:

        inputs = tokenizer.apply_chat_template(
            messages,
            tokenize=True,
            add_generation_prompt=True,
            return_tensors="pt"
        )

        # Move input to the same device as the model
        inputs = inputs.to(model.device)

        outputs = model.generate(
            input_ids=inputs,
            max_new_tokens=1024,
            temperature=0.3,
            use_cache=True
        )

        generated_tokens = outputs[0][inputs.shape[1]:]

        raw_response = tokenizer.decode(
            generated_tokens,
            skip_special_tokens=True
        ).strip()

        print("\nAI RESPONSE:")
        print(raw_response)

        parsed_json = json.loads(raw_response)

        return parsed_json

    except json.JSONDecodeError:
        raise HTTPException(
            status_code=500,
            detail="The AI returned an invalid JSON response."
        )

    except Exception as e:
        print(f"AI ERROR: {e}")

        raise HTTPException(
            status_code=500,
            detail=str(e)
        )


# ============================================================
# Health check
# ============================================================

@app.get("/health")
async def health():

    return {
        "status": "ok",
        "service": "Smart Idea Evaluation AI"
    }


# ============================================================
# Start server
# ============================================================

if __name__ == "__main__":

    import uvicorn

    uvicorn.run(
        app,
        host="0.0.0.0",
        port=8000
    )
