import google.genai as genai
import openai
import os
import json


class FormGenerator:

    def __init__(self):
        self.provider = os.getenv("AI_PROVIDER", "gemini")
        self.model = self._get_model()
        self.system_prompt = """You are a form builder AI. The user will describe a form in natural language.
You must respond with ONLY valid JSON, nothing else. No explanation, no markdown.
Use this exact format:
{
  "title": "Form title",
  "description": "Form description",
  "fields": [
    {
      "label": "Field name",
      "type": "text|email|number|date|select|textarea|checkbox|radio",
      "required": true or false,
      "options": ["option1", "option2"]
    }
  ]
}
Only include "options" for select, checkbox, and radio types."""

        # Initialize the right client based on provider
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
            return "gemini-2.0-flash"
        return "gpt-4o-mini"

    def _clean_json(self, text: str) -> str:
        """Strip markdown code blocks that AI sometimes wraps around JSON"""
        text = text.strip()
        if text.startswith("```json"):
            text = text[7:]
        elif text.startswith("```"):
            text = text[3:]
        if text.endswith("```"):
            text = text[:-3]
        return text.strip()

    def generate(self, prompt: str) -> dict:
        """
        Send prompt to AI, get form structure back

        Args:
            prompt: Natural language form description
        Returns:
            dict with title, description, and fields
        """
        if self.provider == "gemini":
            raw = self._gemini_request(prompt)
        else:
            raw = self._openai_request(prompt)

        clean = self._clean_json(raw)

        try:
            return json.loads(clean)
        except json.JSONDecodeError as e:
            raise ValueError(f"Failed to parse AI response as JSON: {e}")

    def _gemini_request(self, prompt: str) -> str:
        """Send prompt to Gemini and return raw text response"""
        response = self.client.models.generate_content(
            model=self.model,
            contents=prompt,
            config={
                "system_instruction": self.system_prompt
            }
        )
        return response.text

    def _openai_request(self, prompt: str) -> str:
        """Send prompt to OpenAI and return raw text response"""
        response = self.client.chat.completions.create(
            model=self.model,
            messages=[
                {"role": "system", "content": self.system_prompt},
                {"role": "user", "content": prompt}
            ]
        )
        return response.choices[0].message.content