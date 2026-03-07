from flask import Flask, jsonify, request
from flask_cors import CORS
from generator import FormGenerator
from analyst import FormAnalyst

app = Flask(__name__)
CORS(app)

# Initialize services once when server starts
generator = FormGenerator()
analyst = FormAnalyst()


@app.route("/health", methods=["GET", "POST"])
def health():
    return jsonify({"status": "ok", "service": "NexForm AI"})


@app.route("/generate", methods=["POST"])
def generate():
    data = request.get_json()

    if not data or "prompt" not in data:
        return jsonify({"error": "prompt is required"}), 400

    prompt = data["prompt"].strip()

    if not prompt:
        return jsonify({"error": "prompt cannot be empty"}), 400

    try:
        result = generator.generate(prompt)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route("/analyze", methods=["POST"])
def analyze():
    data = request.get_json()

    # Check required fields
    if not data or "form_id" not in data or "question" not in data:
        return jsonify({"error": "form_id and question are required"}), 400

    form_id = data["form_id"]
    question = data["question"].strip()

    if not question:
        return jsonify({"error": "question cannot be empty"}), 400

    try:
        result = analyst.analyze(form_id, question)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)