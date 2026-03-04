from flask import Flask, jsonify, request
from flask_cors import CORS
from generator import FormGenerator

app = Flask(__name__)
CORS(app)

# Initialize form generator once when server starts
generator = FormGenerator()


@app.route("/health", methods=["GET", "POST"])
def health():
    return jsonify({"status": "ok", "service": "NexForm AI"})


@app.route("/generate", methods=["POST"])
def generate():
    data = request.get_json()

    # Check if prompt exists
    if not data or "prompt" not in data:
        return jsonify({"error": "prompt is required"}), 400

    prompt = data["prompt"].strip()

    # Check if prompt is empty
    if not prompt:
        return jsonify({"error": "prompt cannot be empty"}), 400

    try:
        result = generator.generate(prompt)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)