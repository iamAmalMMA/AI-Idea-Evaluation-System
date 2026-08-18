from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel


app = FastAPI(
    title="Smart Idea Evaluation AI - Mock Server",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


class IdeaRequest(BaseModel):
    title: str
    description: str


@app.get("/health")
async def health():
    return {
        "status": "ok",
        "service": "Mock Idea Evaluation AI"
    }


@app.post("/evaluate")
async def evaluate_idea(payload: IdeaRequest):

    title = payload.title.strip()
    description = payload.description.strip()

    if not title and not description:
        raise HTTPException(
            status_code=400,
            detail="Please provide an idea title or description."
        )

    # Simulated response matching the structure
    # expected by the existing PHP backend.
    return {
        "title": title,
        "description": description,
        "evaluation": {
            "scores": {
                "innovation": {
                    "score": 4.0,
                    "reason": "الفكرة تقدم حلاً مبتكراً لمشكلة واضحة."
                },
                "feasibility": {
                    "score": 4.5,
                    "reason": "يمكن تنفيذ الفكرة باستخدام تقنيات وموارد متاحة."
                },
                "business_value": {
                    "score": 4.0,
                    "reason": "للفكرة قيمة واضحة ويمكن أن تحقق فوائد ملموسة."
                },
                "sustainability": {
                    "score": 4.0,
                    "reason": "يمكن استدامة الحل مع وجود آلية مناسبة للتشغيل والمتابعة."
                },
                "cost": {
                    "score": 4.0,
                    "reason": "التكلفة المتوقعة قابلة للإدارة مقارنة بالفوائد المحتملة."
                }
            },
            "overall_score": 4.1,
            "strengths": [
                "الفكرة تعالج مشكلة واضحة.",
                "يمكن تنفيذها باستخدام تقنيات متاحة."
            ],
            "improvement_opportunities": [
                "تحديد آلية التنفيذ بشكل أكثر تفصيلاً.",
                "توضيح الموارد المطلوبة."
            ],
            "improved_proposal": {
                "suggested_title": f"تطوير {title}",
                "suggested_description": (
                    "يمكن تطوير الفكرة من خلال إضافة آلية واضحة "
                    "للتنفيذ والمتابعة وقياس النتائج."
                )
            }
        }
    }


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        app,
        host="0.0.0.0",
        port=8000
    )