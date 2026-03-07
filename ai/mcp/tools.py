import mysql.connector
import json


def _connect(db_config: dict):
    """Create and return a database connection"""
    return mysql.connector.connect(**db_config)


def get_form(form_id: int, db_config: dict) -> dict:
    """
    Fetch form details from database

    Args:
        form_id: ID of the form
        db_config: Database connection parameters
    Returns:
        dict with form title, description, and field schema
    """
    conn = _connect(db_config)
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT * FROM forms WHERE id = %s", (form_id,))
    form = cursor.fetchone()

    cursor.close()
    conn.close()

    if not form:
        return {}

    # Parse schema_json string into dict
    form['schema_json'] = json.loads(form['schema_json'])
    return form


def get_responses(form_id: int, db_config: dict) -> list:
    """
    Fetch all responses for a form

    Args:
        form_id: ID of the form
        db_config: Database connection parameters
    Returns:
        list of response dicts with parsed JSON data
    """
    conn = _connect(db_config)
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT * FROM responses WHERE form_id = %s", (form_id,))
    responses = cursor.fetchall()

    cursor.close()
    conn.close()

    # Parse response_data JSON string for each response
    for r in responses:
        r['response_data'] = json.loads(r['response_data'])

    return responses


def count_responses(form_id: int, db_config: dict) -> int:
    """
    Count total responses for a form

    Args:
        form_id: ID of the form
        db_config: Database connection parameters
    Returns:
        Number of responses
    """
    conn = _connect(db_config)
    cursor = conn.cursor()

    cursor.execute("SELECT COUNT(*) FROM responses WHERE form_id = %s", (form_id,))
    count = cursor.fetchone()[0]

    cursor.close()
    conn.close()

    return count