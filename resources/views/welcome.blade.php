<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>AI Notes Manager</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .header {
            background: #111827;
            color: white;
            padding: 20px 40px;
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            border: none;
            padding: 10px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-ai {
            background: #7c3aed;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .notes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .note {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            background: white;
        }

        .note h3 {
            margin-top: 0;
        }

        .note-content {
            color: #6b7280;
            line-height: 1.6;
        }

        .summary {
            background: #f3e8ff;
            border-left: 4px solid #7c3aed;
            padding: 12px;
            margin-top: 15px;
            border-radius: 5px;
        }

        .message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 7px;
            display: none;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
        }
    </style>
</head>

<body>

<header class="header">
    <h1>🤖 AI Notes Manager</h1>
</header>

<div class="container">

    <div id="message" class="message"></div>

    <!-- Create Note -->

    <div class="card">

        <h2>Create Note</h2>

        <form id="noteForm">

            <div class="form-group">

                <label>Title</label>

                <input
                    type="text"
                    id="title"
                    placeholder="Enter note title"
                    required
                >

            </div>

            <div class="form-group">

                <label>Content</label>

                <textarea
                    id="content"
                    placeholder="Write your note..."
                    required
                ></textarea>

            </div>

            <button
                type="submit"
                class="btn-primary">
                Create Note
            </button>

        </form>

    </div>


    <!-- Search -->

    <div class="card">

        <h2>Search Notes</h2>

        <div class="search-box">

            <input
                type="text"
                id="search"
                placeholder="Search notes..."
            >

            <button
                class="btn-primary"
                onclick="loadNotes()">
                Search
            </button>

        </div>

    </div>


    <!-- Notes -->

    <div class="card">

        <h2>My Notes</h2>

        <div
            id="notes"
            class="notes-grid">
        </div>

    </div>

</div>


<script>

const API = '/api/notes';

const messageBox = document.getElementById('message');


function showMessage(message, type = 'success')
{
    messageBox.innerText = message;

    messageBox.className = `message ${type}`;

    messageBox.style.display = 'block';

    setTimeout(() => {
        messageBox.style.display = 'none';
    }, 3000);
}


/*
|--------------------------------------------------------------------------
| Load Notes
|--------------------------------------------------------------------------
*/

async function loadNotes()
{
    try {

        const search =
            document.getElementById('search').value.trim();

        let url = API;

        /*
         * If search text exists,
         * use AI semantic search.
         */
        if (search) {

            url += '/search?q=' +
                encodeURIComponent(search);
        }

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!response.ok) {

            throw new Error(
                result.message ||
                'Unable to load notes'
            );
        }

        /*
         * Semantic search response:
         *
         * data = [
         *   {
         *      note: {...},
         *      similarity: 0.85
         *   }
         * ]
         *
         * Normal response:
         *
         * data = [
         *   {...}
         * ]
         */

        const notes = search
            ? result.data.map(item => item.note)
            : result.data;

        renderNotes(notes);

    }
    catch (error) {

        showMessage(
            error.message,
            'error'
        );
    }
}


/*
|--------------------------------------------------------------------------
| Render Notes
|--------------------------------------------------------------------------
*/

function renderNotes(notes)
{
    const container =
        document.getElementById('notes');

    container.innerHTML = '';

    if (!notes || notes.length === 0) {

        container.innerHTML =
            '<p>No notes found.</p>';

        return;
    }

    notes.forEach(note => {

        const div =
            document.createElement('div');

        div.className = 'note';

        div.innerHTML = `

            <h3>
                ${escapeHtml(note.title)}
            </h3>

            <p class="note-content">
                ${escapeHtml(note.content)}
            </p>

            ${
                note.summary
                ?
                `
                <div class="summary">

                    <strong>AI Summary</strong>

                    <p>
                        ${escapeHtml(note.summary)}
                    </p>

                </div>
                `
                :
                ''
            }

            <br>

            <button
                class="btn-ai"
                onclick="generateSummary(${note.id})">

                ✨ AI Summary

            </button>

            <button
                class="btn-danger"
                onclick="deleteNote(${note.id})">

                Delete

            </button>

        `;

        container.appendChild(div);

    });
}


/*
|--------------------------------------------------------------------------
| Create Note
|--------------------------------------------------------------------------
*/

async function createNote(event)
{
    event.preventDefault();

    const title =
        document.getElementById('title').value.trim();

    const content =
        document.getElementById('content').value.trim();

    try {

        const response = await fetch(API, {

            method: 'POST',

            headers: {

                'Content-Type':
                    'application/json',

                'Accept':
                    'application/json'

            },

            body: JSON.stringify({
                title: title,
                content: content
            })

        });

        const result =
            await response.json();

        if (!response.ok) {

            throw new Error(
                result.message ||
                'Unable to create note'
            );

        }

        showMessage(
            'Note created successfully!'
        );

        document
            .getElementById('noteForm')
            .reset();

        loadNotes();

    }
    catch (error) {

        showMessage(
            error.message,
            'error'
        );

    }
}


/*
|--------------------------------------------------------------------------
| Delete Note
|--------------------------------------------------------------------------
*/

async function deleteNote(id)
{
    if (!confirm('Delete this note?')) {
        return;
    }

    try {

        const response =
            await fetch(`${API}/${id}`, {

                method: 'DELETE',

                headers: {
                    'Accept':
                        'application/json'
                }

            });

        const result =
            await response.json();

        if (!response.ok) {

            throw new Error(
                result.message ||
                'Unable to delete note'
            );

        }

        showMessage(
            'Note deleted successfully!'
        );

        loadNotes();

    }
    catch (error) {

        showMessage(
            error.message,
            'error'
        );

    }
}


/*
|--------------------------------------------------------------------------
| Generate AI Summary
|--------------------------------------------------------------------------
*/

async function generateSummary(id)
{
    showMessage(
        'Generating AI summary...'
    );

    try {

        const response =
            await fetch(
                `${API}/${id}/summary`,
                {

                    method: 'POST',

                    headers: {
                        'Accept':
                            'application/json'
                    }

                }
            );

        const result =
            await response.json();

        if (!response.ok) {

            throw new Error(
                result.message ||
                'Unable to generate summary'
            );

        }

        showMessage(
            'AI summary generated!'
        );

        loadNotes();

    }
    catch (error) {

        showMessage(
            error.message,
            'error'
        );

    }
}


/*
|--------------------------------------------------------------------------
| Prevent XSS
|--------------------------------------------------------------------------
*/

function escapeHtml(value)
{
    const div =
        document.createElement('div');

    div.textContent =
        value ?? '';

    return div.innerHTML;
}


/*
|--------------------------------------------------------------------------
| Form Submit
|--------------------------------------------------------------------------
*/

document
    .getElementById('noteForm')
    .addEventListener(
        'submit',
        createNote
    );


/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

loadNotes();

</script>

</body>
</html>