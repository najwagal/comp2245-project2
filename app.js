'use strict';

(function () {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) {
                    throw new Error(data.error || 'Something went wrong. Please try again.');
                }
                return data;
            });
        });
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    }

    function slug(value) {
        return String(value).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }

    /* ---------------- Ticket list (index.php) ---------------- */
    var rows = document.getElementById('ticket-rows');
    if (rows) {
        var chips = document.querySelectorAll('.chip[data-filter]');
        var countEl = document.getElementById('ticket-count');
        var listError = document.getElementById('list-error');
        var latest = 0;

        function pill(prefix, value) {
            return el('span', 'pill ' + prefix + '-' + slug(value), value);
        }

        function cell(label) {
            var td = el('td');
            td.setAttribute('data-label', label);
            return td;
        }

        function renderRows(tickets) {
            rows.textContent = '';
            tickets.forEach(function (t) {
                var tr = el('tr');
                var td;

                td = cell('#'); td.textContent = t.id; tr.appendChild(td);

                td = cell('Title');
                var a = el('a', '', t.title);
                a.href = 'ticket.php?id=' + encodeURIComponent(t.id);
                td.appendChild(a); tr.appendChild(td);

                td = cell('Priority'); td.appendChild(pill('priority', t.priority)); tr.appendChild(td);
                td = cell('Status'); td.appendChild(pill('status', t.status)); tr.appendChild(td);
                td = cell('Created by'); td.textContent = t.creator_name; tr.appendChild(td);

                td = cell('Assigned to');
                if (t.assignee_name === null) {
                    td.appendChild(el('span', 'muted', 'Nobody'));
                } else {
                    td.textContent = t.assignee_name;
                }
                tr.appendChild(td);

                td = cell('Created'); td.textContent = t.created_at; tr.appendChild(td);
                rows.appendChild(tr);
            });
            if (tickets.length === 0) {
                var empty = el('tr');
                var emptyCell = el('td', 'muted', 'No tickets match this filter.');
                emptyCell.colSpan = 7;
                empty.appendChild(emptyCell);
                rows.appendChild(empty);
            }
        }

        function loadTickets(filter) {
            var request = ++latest;
            listError.hidden = true;
            fetch('api/tickets.php?filter=' + encodeURIComponent(filter), { credentials: 'same-origin' })
                .then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (data) {
                        if (!res.ok) { throw new Error(data.error || 'Could not load tickets.'); }
                        return data;
                    });
                })
                .then(function (data) {
                    if (request !== latest) { return; } // a newer click already won
                    renderRows(data.tickets);
                    countEl.textContent = data.count + ' ticket' + (data.count === 1 ? '' : 's');
                })
                .catch(function (err) {
                    if (request !== latest) { return; }
                    listError.textContent = err.message;
                    listError.hidden = false;
                });
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chips.forEach(function (c) {
                    var on = c === chip;
                    c.classList.toggle('active', on);
                    c.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                loadTickets(chip.getAttribute('data-filter'));
            });
        });
    }

    /* ---------------- Ticket page (ticket.php) ---------------- */
    var page = document.getElementById('ticket-page');
    if (page) {
        var ticketId = parseInt(page.getAttribute('data-ticket-id'), 10);
        var userId = parseInt(page.getAttribute('data-user-id'), 10);
        var actionMsg = document.getElementById('action-msg');

        function say(text, isError) {
            actionMsg.textContent = text;
            actionMsg.classList.toggle('error', !!isError);
        }

        // Delete confirmation
        var deleteBtn = document.getElementById('delete-btn');
        var confirmBox = document.getElementById('delete-confirm');
        var deleteCancel = document.getElementById('delete-cancel');
        if (deleteBtn && confirmBox) {
            deleteBtn.addEventListener('click', function () { confirmBox.hidden = false; });
            if (deleteCancel) {
                deleteCancel.addEventListener('click', function () { confirmBox.hidden = true; });
            }
        }

        function applyTicket(t) {
            var statusPill = document.getElementById('status-pill');
            statusPill.textContent = t.status;
            statusPill.className = 'pill status-' + slug(t.status);
            document.getElementById('assigned-name').textContent = t.assignee_name === null ? 'Nobody' : t.assignee_name;
            document.getElementById('updated-at').textContent = t.updated_at;
            document.getElementById('status-select').value = t.status;
            var assignBtn = document.getElementById('assign-btn');
            if (assignBtn) { assignBtn.hidden = t.assigned_to === userId; }
        }

        // Change status
        var statusSelect = document.getElementById('status-select');
        statusSelect.addEventListener('change', function () {
            var previous = document.getElementById('status-pill').textContent;
            statusSelect.disabled = true;
            say('Saving…', false);
            postJson('api/ticket-update.php', { ticket_id: ticketId, action: 'status', status: statusSelect.value })
                .then(function (data) { applyTicket(data.ticket); say('Status updated.', false); })
                .catch(function (err) { statusSelect.value = previous; say(err.message, true); })
                .then(function () { statusSelect.disabled = false; });
        });

        // Assign to me
        var assignBtn = document.getElementById('assign-btn');
        if (assignBtn) {
            assignBtn.addEventListener('click', function () {
                assignBtn.disabled = true;
                say('Saving…', false);
                postJson('api/ticket-update.php', { ticket_id: ticketId, action: 'assign' })
                    .then(function (data) { applyTicket(data.ticket); say('Assigned to you.', false); })
                    .catch(function (err) { say(err.message, true); })
                    .then(function () { assignBtn.disabled = false; });
            });
        }

        // Add a note
        var noteForm = document.getElementById('note-form');
        var noteBody = document.getElementById('note-body');
        var noteError = document.getElementById('note-error');
        noteForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var body = noteBody.value.trim();
            noteError.hidden = true;
            if (body === '') {
                noteError.textContent = 'Write something before adding a note.';
                noteError.hidden = false;
                return;
            }
            var submit = noteForm.querySelector('button');
            submit.disabled = true;
            postJson('api/notes.php', { ticket_id: ticketId, body: body })
                .then(function (data) {
                    var n = data.note;
                    var article = el('article', 'note');
                    var meta = el('p', 'note-meta');
                    meta.appendChild(el('strong', '', n.author));
                    meta.appendChild(document.createTextNode(' · ' + n.created_at));
                    article.appendChild(meta);
                    article.appendChild(el('p', 'note-body', n.body));
                    document.getElementById('notes').appendChild(article);
                    var empty = document.getElementById('notes-empty');
                    if (empty) { empty.remove(); }
                    noteBody.value = '';
                })
                .catch(function (err) {
                    noteError.textContent = err.message;
                    noteError.hidden = false;
                })
                .then(function () { submit.disabled = false; });
        });
    }
})();
