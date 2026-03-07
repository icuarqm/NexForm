import google.genai as genai
import openai
import os
import json
from mcp.tools import get_form, get_responses, count_responses


class FormAnalyst:

    def __init__(self):
        self.provider = os.getenv("AI_PROVIDER", "gemini")
        self.model = self._get_model()

        if self.provider == "gemini":
            api_key = os.getenv("GEMINI_API_KEY")
            if not api_key:
                raise ValueError("GEMINI_API_KEY not found")
            self.client = genai.Client(api_key=api_key)
        else:
            api_key = os.getenv("OPENAI_API_KEY")
            if not api_key:
                raise ValueError("OPENAI_API_KEY not found")
            self.client = openai.OpenAI(api_key=api_key)

    def _get_model(self) -> str:
        """Return default model name based on provider"""
        if self.provider == "gemini":
            return "gemini-2.5-flash"
        return "gpt-4o-mini"

    def _get_db_config(self) -> dict:
        """Build database config from environment variables"""
        return {
            "host": os.getenv("MYSQL_HOST", "db"),
            "database": os.getenv("MYSQL_DATABASE", "nexform"),
            "user": os.getenv("MYSQL_USER", "root"),
            "password": os.getenv("MYSQL_ROOT_PASSWORD", "root")
        }

    def analyze(self, form_id: int, question: str) -> dict:
        """
        Analyze form responses using AI

        Args:
            form_id: ID of the form to analyze
            question: User's question about the responses
        Returns:
            dict with answer and tools_used
        """
        db_config = self._get_db_config()
        tools_used = []

        # Fetch form details
        form = get_form(form_id, db_config)
        tools_used.append("get_form")

        if not form:
            return {"answer": "Form not found.", "tools_used": tools_used}

        # Fetch all responses
        responses = get_responses(form_id, db_config)
        tools_used.append("get_responses")

        # Get response count
        total = count_responses(form_id, db_config)
        tools_used.append("count_responses")

        if total == 0:
            return {"answer": "No responses yet for this form.", "tools_used": tools_used}

        # Build context for AI
        prompt = self._build_prompt(form, responses, total, question)

        # Ask AI
        if self.provider == "gemini":
            answer = self._gemini_request(prompt)
        else:
            answer = self._openai_request(prompt)

        # Log analysis to database
        self._save_log(form_id, question, answer, tools_used, db_config)

        return {"answer": answer, "tools_used": tools_used}

    def _build_prompt(self, form: dict, responses: list, total: int, question: str) -> str:
        """Build a detailed prompt with form context and responses"""
        schema = form.get('schema_json', {})
        fields = schema.get('fields', [])

        # Map field_0, field_1 etc. to actual labels
        field_map = {f"field_{i}": f['label'] for i, f in enumerate(fields)}

        # Convert responses to readable format
        readable_responses = []
        for r in responses:
            data = r.get('response_data', {})
            readable = {}
            for key, value in data.items():
                label = field_map.get(key, key)
                readable[label] = value
            readable_responses.append(readable)

        return f"""You are a form response analyst. Analyze the following form data and answer the user's question.

Form: {schema.get('title', 'Unknown')}
Description: {schema.get('description', '')}
Total responses: {total}

Fields: {json.dumps([f['label'] for f in fields])}

Responses:
{json.dumps(readable_responses, indent=2)}

User's question: {question}

Give a clear, concise answer based only on the data above."""

    def _gemini_request(self, prompt: str) -> str:
        """Send prompt to Gemini and return text response"""
        response = self.client.models.generate_content(
            model=self.model,
            contents=prompt
        )
        return response.text

    def _openai_request(self, prompt: str) -> str:
        """Send prompt to OpenAI and return text response"""
        response = self.client.chat.completions.create(
            model=self.model,
            messages=[{"role": "user", "content": prompt}]
        )
        return response.choices[0].message.content

    def _save_log(self, form_id: int, question: str, answer: str, tools_used: list, db_config: dict):
        """Save analysis log to database"""
        import mysql.connector

        try:
            conn = mysql.connector.connect(**db_config)
            cursor = conn.cursor()

            cursor.execute(
                "INSERT INTO analysis_logs (form_id, query_text, agent_response, tools_used) VALUES (%s, %s, %s, %s)",
                (form_id, question, json.dumps({"answer": answer}), json.dumps(tools_used))
            )

            conn.commit()
            cursor.close()
            conn.close()
        except Exception:
            pass