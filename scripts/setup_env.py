from pathlib import Path


def print_banner():
    print("\n===================================")
    print("        NexForm Environment Setup")
    print("===================================\n")
    print("This script will generate a .env file")
    print("based on .env.example and guide you")
    print("through the required configuration.\n")


def ask_ai_provider():
    print("Select the AI provider you want to use:\n")
    print("1) Gemini")
    print("2) OpenAI\n")

    while True:
        choice = input("Enter your choice (1 or 2): ").strip()

        if choice == "1":
            return "gemini"
        elif choice == "2":
            return "openai"
        else:
            print("Invalid choice. Please enter 1 or 2.\n")


def main():
    root = Path(__file__).resolve().parent.parent
    env_example = root / ".env.example"
    env_file = root / ".env"

    print_banner()

    if not env_example.exists():
        print("Error: .env.example file was not found.\n")
        return

    if env_file.exists():
        answer = input(".env already exists. Overwrite it? (y/N): ").strip().lower()
        if answer not in {"y", "yes"}:
            print("\nSetup cancelled. Existing .env was not modified.\n")
            return

    provider = ask_ai_provider()

    print()

    if provider == "gemini":
        api_key = input("Enter your Gemini API key: ").strip()
    else:
        api_key = input("Enter your OpenAI API key: ").strip()

    print()

    admin_secret = input(
        "Enter an ADMIN_SECRET_KEY (used for admin panel access): "
    ).strip()

    print()

    lines = env_example.read_text().splitlines()
    new_lines = []

    for line in lines:

        if line.startswith("AI_PROVIDER="):
            new_lines.append(f"AI_PROVIDER={provider}")

        elif line.startswith("GEMINI_API_KEY="):
            if provider == "gemini":
                new_lines.append(f"GEMINI_API_KEY={api_key}")
            else:
                new_lines.append("GEMINI_API_KEY=")

        elif line.startswith("OPENAI_API_KEY="):
            if provider == "openai":
                new_lines.append(f"OPENAI_API_KEY={api_key}")
            else:
                new_lines.append("OPENAI_API_KEY=")

        elif line.startswith("ADMIN_SECRET_KEY="):
            new_lines.append(f"ADMIN_SECRET_KEY={admin_secret}")

        else:
            new_lines.append(line)

    env_file.write_text("\n".join(new_lines))

    print("✔ .env file created successfully.\n")
    print("If you want to change other configuration values,")
    print("you can edit the .env file manually.\n")
    print("You can now start the project using:\n")
    print("docker compose up --build -d\n")


if __name__ == "__main__":
    main()
