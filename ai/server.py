from flask import Flask, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "service": "NexForm AI"})

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
