<?php
/**
 * Corenote - Plain-text note management in a single PHP file
 *
 * @copyright 2018 Makepost
 * @license MIT
 */

class Note
{
    public $createdAt = 0;

    public $dir = '';

    public $value = '';

    /**
     * @param Note $note
     */
    public static function ensure($note)
    {
        if (!filter_var($note->createdAt, FILTER_VALIDATE_FLOAT)) {
            throw new Exception('Bad request');
        }

        if (!is_string($note->dir)) {
            throw new Exception('Bad request');
        }

        if (preg_match('/^\.|\.$/', $note->dir)) {
            throw new Exception('Bad request');
        }

        if (false !== mb_strpos($note->dir, '.'.DIRECTORY_SEPARATOR)) {
            throw new Exception('Bad request');
        }

        if (false !== mb_strpos($note->dir, DIRECTORY_SEPARATOR.'.')) {
            throw new Exception('Bad request');
        }

        if (!is_string($note->value)) {
            throw new Exception('Bad request');
        }
    }
}

class NoteService
{
    /**
     * @param Note[] notes
     */
    public function delete($notes)
    {
        foreach ($notes as $note) {
            Note::ensure($note);

            unlink($this->sub([
                $note->dir,
                $note->createdAt.'.txt',
            ]));

            while (2 === count(scandir($this->sub([$note->dir])))) {
                rmdir($this->sub([$note->dir]));

                $note->dir = dirname($note->dir);
            }
        }

        return $this->get();
    }

    public function get()
    {
        $files = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__)
            ),
            '/\/(\d+)\.txt$/',
            RegexIterator::GET_MATCH
        );

        /** @var Note[] */
        $notes = [];

        foreach ($files as $path => $matches) {
            $dir = ltrim(
                str_replace(__DIR__, '', dirname($path)),
                DIRECTORY_SEPARATOR
            );

            $note = new Note();
            $note->createdAt = floatval($matches[1]);
            $note->dir = $dir;
            $note->value = file_get_contents($path);

            array_push($notes, $note);
        }

        usort($notes, function ($a, $b) {
            return $b->createdAt - $a->createdAt;
        });

        return $notes;
    }

    /**
     * @param Note[] notes
     */
    public function post($notes)
    {
        foreach ($notes as $note) {
            Note::ensure($note);

            mkdir($note->dir, 0755, true);

            file_put_contents(
                $this->sub([
                    $note->dir,
                    $note->createdAt.'.txt',
                ]),
                $note->value
            );
        }

        return $this->get();
    }

    /**
     * @param string[] $components
     */
    private function sub($components)
    {
        array_unshift($components, __DIR__);

        return implode(DIRECTORY_SEPARATOR, $components);
    }
}

$uri = $_SERVER['QUERY_STRING'];
$method = mb_strtolower($_SERVER['REQUEST_METHOD']);

if ('/' === $uri && $method === 'get') {
    header('Content-Type: text/x-php');
    echo file_get_contents(__FILE__);
    exit;
}

if ('/notes' === $uri) {
    $noteService = new NoteService();

    if ('delete' === $method || 'post' === $method) {
        $body = json_decode(file_get_contents('php://input'));
        echo json_encode($noteService->$method($body));
        exit;
    }

    if ('get' === $method) {
        echo json_encode($noteService->get());
        exit;
    }
}
?>
<!DOCTYPE html>

<meta charset="utf-8" />

<title>Corenote</title>

<link rel="shortcut icon" href="data:image/vnd.microsoft.icon;base64,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" />

<meta name="viewport" content="width=device-width, initial-zoom=1" />

<style>
* {
    box-sizing: border-box;
    font-size: 16px; /** Prevents zoom on touch. */
    margin-left: auto;
    margin-right: auto;
}

html,
body,
textarea {
    height: 100%;
}

body {
    line-height: 1.7;
    margin: 0;
    padding: 2em 0 0;
}

div,
textarea {
    max-width: 40em;
    padding: 0.5em 1em 2.5em;
}

textarea {
    background-color: transparent;
    border: none;
    display: block;
    font: inherit;
    outline: none !important;
    -webkit-overflow-scrolling: touch;
    overflow-y: auto;
    width: 100%;
}

button,
div,
select {
    position: absolute;
}

button,
select {
    height: 2em;
}


button {
    bottom: 0;
    width: 10em;
}

div,
select {
    top: 0;
}

div {
    display: table;
}

select {
    width: 50%;
}

:first-of-type {
    left: 0;
}

:last-of-type {
    right: 0;
}

select:not(:empty) ~ div,
select:empty,
select:empty ~ button,
textarea:not(:invalid) ~ div {
    display: none;
}
</style>

<select id="dirs"></select>

<select id="createdAts"></select>

<textarea autofocus required></textarea>

<div>
    <h1>The hardest way to keep notes.</h1>

    <ul>
        <li>Saves versions, you can always undo.</li>

        <li>Works in relatively old browsers, mobile too.</li>

        <li>One PHP file.</li>

        <li>Persists on server as plain text.</li>

        <li>Groups by first line, which acts as title.</li>

        <li>Just start typing.</li>
    </ul>
</div>

<button>Delete</button>

<script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=default,fetch"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-throttle-debounce/1.1/jquery.ba-throttle-debounce.min.js"></script>

<script>
/**
 * @param {number} delay
 */
function sleep(delay) {
    return new Promise(function(resolve) {
        window.setTimeout(resolve, delay);
    });
}

/** @type {typeof window.fetch} */
var _fetch = window.fetch.bind(window);

var alertedAt = 0;
var prevAlert = "";

window.fetch = function(url, options) {
    var delay = 1e3;

    function _() {
        return sleep(delay *= 2).then(function() {
            return Promise.race([
                _fetch(url, options),

                sleep(1e4).then(function() {
                    throw new Error("Timed out");
                })
            ])
        });
    }

    return _fetch(url, options)
        .catch(_)
        .catch(_)
        .catch(_)
        .catch(_)
        .catch(function(error) {
            var now = Date.now();

            if (prevAlert !== error.message || now - alertedAt >= 6e4) {
                alertedAt = now;
                prevAlert = error.message;

                window.alert(error.message);
            }

            throw error;
        });
};

function Note() {
    this.createdAt = 0;

    this.dir = "";

    this.value = "";
}

var $createdAts = document.querySelector("#createdAts");
var $delete = document.querySelector("button");
var $dirs = document.querySelector("#dirs");
var $note = document.querySelector("textarea");

var noteService = {
    apiBase: "./notes.php?",

    createdAt: 0,

    dir: "",

    /** @type {Note[]} */
    notes: [],

    undoAge: 15 * 6e4,

    undos: 5
};

function currentNote() {
    var createdAt = noteService.createdAt;
    var dir = noteService.dir;
    var notes = noteService.notes;

    for (var i = 0; i < notes.length; i++) {
        var note = notes[i];

        if (note.dir === dir && note.createdAt === createdAt) {
            return note;
        }
    }

    return notes
        .filter(function(x) {
            return x = x.dir === dir;
        })
        .sort(function(a, b) {
            return Math.abs(a.createdAt - createdAt) -
                Math.abs(b.createdAt - createdAt);
        })
        [0] || (notes.length ? notes[0] : new Note());
}

function render() {
    var $options = document.querySelectorAll("option");

    for (var i = 0; i < $options.length; i++) {
        $options[i].parentNode.removeChild($options[i]);
    }

    var notes = noteService.notes;
    var latest = true;

    for (var i = 0; i < notes.length; i++) {
        if (notes[i].dir !== noteService.dir) {
            continue;
        }

        var $createdAt = document.createElement("option");

        $createdAt.textContent = latest
            ? "Latest"
            : new Date(notes[i].createdAt)
                .toISOString()
                .replace("T", " ")
                .replace(/\.\d+Z/, " UTC");

        $createdAt.value = notes[i].createdAt;
        $createdAts.appendChild($createdAt);

        latest = false;
    }

    $createdAts.value = noteService.createdAt;

    dirs: for (var i = 0; i < notes.length; i++) {
        for (var j = 0; j < i; j++) {
            if (notes[i].dir === notes[j].dir) {
                continue dirs;
            }
        }

        var $dir = document.createElement("option");
        $dir.textContent = notes[i].dir;
        $dir.value = notes[i].dir;
        $dirs.appendChild($dir);
    }

    $dirs.value = noteService.dir;
}

function renderNote() {
    $note.value = currentNote().value;
}

$createdAts.addEventListener("change", function(ev) {
    noteService.createdAt = Number(ev.target.value);

    render();
    renderNote();
});

$delete.addEventListener("click", function() {
    if (!window.confirm("Delete version?")) {
        return;
    }

    $delete.disabled = true;
    timeout = 0;

    localStorage.removeItem(
        currentNote().dir + "/" + currentNote().createdAt + ".txt"
    );

    fetch(noteService.apiBase + "/notes", {
        body: JSON.stringify([currentNote()]),
        method: "DELETE"
    })
        .then(function(x) {
            return x.json();
        })
        .then(function(_) {
            noteService.notes = _;

            var note = currentNote();
            noteService.createdAt = note.createdAt;
            noteService.dir = note.dir;

            render();
            renderNote();
        })
        .finally(function() {
            $delete.disabled = false;
        });
});

$dirs.addEventListener("change", function(ev) {
    noteService.dir = ev.target.value;

    var notes = noteService.notes;

    for (var i = 0; i < notes.length; i++) {
        if (notes[i].dir === noteService.dir) {
            noteService.createdAt = notes[i].createdAt;
            break;
        }
    }

    render();
    renderNote();
});

var prevValue = "";
$note.addEventListener(
    "input",
    Cowboy.debounce(5e3, function(ev) {
        var note = new Note();

        note.value = ev.target.value;

        if (note.value === prevValue) {
            return;
        }

        note.createdAt = Date.now();

        note.dir = note.value
            .split("\n")[0]
            .replace(/^\.+|\.+$|\.+[\\\/]+|[\\\/]+\.+/g, "");

        localStorage[note.dir + "/" + note.createdAt + ".txt"] = note.value;

        fetch(noteService.apiBase + "/notes", {
            body: JSON.stringify([note]),
            method: "POST"
        })
            .then(function(x) {
                return x.json();
            })
            .then(function(_) {
                noteService.createdAt = note.createdAt;
                noteService.dir = note.dir;
                noteService.notes = _;

                render();

                var undos = noteService.undos;

                var deleting = noteService.notes
                    .filter(function (x) {
                        return x.dir === note.dir;
                    })
                    .filter(function (x, i, xs) {
                        return (
                            i > 1 &&
                            xs[i - 1].createdAt - x.createdAt <=
                            noteService.undoAge &&
                            --undos <= 0
                        );
                    });

                for (var i = 0; i < deleting.length; i++) {
                    localStorage.removeItem(
                        deleting[i].dir + "/" + deleting[i].createdAt + ".txt"
                    );
                }

                return fetch(noteService.apiBase + "/notes", {
                    body: JSON.stringify(deleting),
                    method: "DELETE"
                })
            })
            .then(function(x) {
                return x.json();
            })
            .then(function(_) {
                noteService.notes = _;

                render();
            });
    })
);

for (var key in localStorage) {
    if (!localStorage.hasOwnProperty(key)) {
        continue;
    }

    var matches = /^(.+)\/(\d+)\.txt$/.exec(key);

    if (!matches) {
        continue;
    }

    noteService.notes.push({
        createdAt: Number(matches[2]),
        dir: matches[1],
        value: localStorage[key]
    });
}

noteService.notes = noteService.notes.sort(function(a, b) {
    return b.createdAt - a.createdAt;
});

fetch(noteService.apiBase + "/notes", {
    body: JSON.stringify(noteService.notes),
    method: "POST"
})
    .then(function(x) {
        return x.json();
    })
    .then(function(/** @type {Note[]} */ _) {
        noteService.notes = _;

        var note = currentNote();
        noteService.createdAt = note.createdAt;
        noteService.dir = note.dir;

        render();
        renderNote();

        for (var i = 0; i < _.length; i++) {
            var x = _[i];
            localStorage[x.dir + "/" + x.createdAt + ".txt"] = x.value;
        }
    });
</script>
