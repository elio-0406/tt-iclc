const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

async function handleSubmit(e) {
  e.preventDefault();

  const titleEl = document.getElementById('titleInput');

  try {
    const res = await fetch('/todos', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ title: titleEl.value })
    });

    if (!res.ok) throw new Error(await res.text());

    const todo = await res.json();

    const li = document.createElement('li');
    li.className = "flex items-center px-4 py-3";
    li.dataset.id = todo.id;
    li.innerHTML = `
      <input type="checkbox" onchange="toggle(${todo.id}, this)"
        class="w-5 h-5 text-green-600 rounded-full border-gray-300 focus:ring-green-500">
      <span class="ml-3 flex-1">${todo.title}</span>
      <button onclick="editTodo(${todo.id}, this)" 
        class="bg-blue-500 hover:bg-blue-600 ml-2 px-3 py-1 rounded-lg">Edit</button>
      <button 
        onclick="removeTodo(${todo.id}, this)" class="bg-red-500 hover:bg-red-600 ml-2 px-3 py-1 rounded-lg">Delete</button>
    `;
    document.getElementById('todoList').prepend(li);
    titleEl.value = '';
    
  console.log(todo);

  } catch (err) {
    console.error('Error:', err);
  }
}

async function toggle(id, checkbox) {
  const res = await fetch(`/todos/${id}/toggle`, {
    method: 'PATCH',
    headers: {
      'X-CSRF-TOKEN': csrf,
      'Accept': 'application/json'
    }
  });
  if (!res.ok) return;

  const todo = await res.json();
  const span = checkbox.nextElementSibling;
  if (todo.is_done) {
    span.classList.add('line-through','text-gray-400');
  } else {
    span.classList.remove('line-through','text-gray-400');
  }

  const li = checkbox.parentElement;
  place(li, todo.is_done);
}

async function removeTodo(id, btn) {
  if (!confirm('Are you sure you want to delete this item?')) return;

  const res = await fetch(`/todos/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': csrf,
      'Accept': 'application/json'
    }
  });
  if (!res.ok) return;
  const data = await res.json();
  if (data.success) {
    btn.parentElement.remove();
  }
}

//lỗi quài z tr
async function editTodo(id, btn) {
  const li   = btn.parentElement;
  const span = li.querySelector('span');
  const old  = span.textContent.trim();

  const input = document.createElement('input');
  input.type = 'text';
  input.value = old;
  input.className = "ml-3 flex-1 border rounded px-2 py-1";
  input.dataset.oldTitle = old;     
  input.name = '';                  
  li.replaceChild(input, span);
  input.focus();

  const commit = () => saveEdit(id, input, li);
  const cancel = () => {             
    if (input.parentElement !== li) return;
    const s = document.createElement('span');
    s.className = span.className || "ml-3 flex-1";
    s.textContent = old;
    li.replaceChild(s, input);
  };

  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); commit(); }
    if (e.key === 'Escape') { e.preventDefault(); cancel(); }
  });
  input.addEventListener('blur', commit);
}

//cấm lỗi nhe chưa
async function saveEdit(id, input, li) {
  if (input._saving) return;           
  const newTitle = input.value.trim();
  const oldTitle = input.dataset.oldTitle || '';

  if (!newTitle) {
    const span = document.createElement('span');
    span.className = "ml-3 flex-1";
    span.textContent = oldTitle;
    if (input.parentElement === li) li.replaceChild(span, input);
    return;
  }

  if (newTitle === oldTitle) {
    const span = document.createElement('span');
    span.className = "ml-3 flex-1";
    span.textContent = oldTitle;
    if (input.parentElement === li) li.replaceChild(span, input);
    return;
  }

  input._saving = true;
  const res = await fetch(`/todos/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      'Accept': 'application/json'
    },
    body: JSON.stringify({ title: newTitle })
  });
  input._saving = false;

  if (res.status === 422) {
    const err = await res.json();
    alert(Object.values(err.errors || {title:['Invalid']}).flat()[0]);
    input.focus();
    return;
  }
  if (!res.ok) { console.error(await res.text()); return; }

  const todo = await res.json();
  const span = document.createElement('span');
  span.className = `ml-3 flex-1 ${todo.is_done ? 'line-through text-gray-400' : ''}`;
  span.textContent = todo.title;
  if (input.parentElement === li) li.replaceChild(span, input);
}


const list = document.getElementById('todoList');

function lastUndoneNode() {
  return [...list.children].reverse().find(
    n => !n.querySelector('input[type="checkbox"]').checked
  ) || null;
}
function firstDoneNode() {
  return [...list.children].find(
    n => n.querySelector('input[type="checkbox"]').checked
  ) || null;
}

function place(li, isDone) {
  if (isDone) {
    const after = lastUndoneNode();
    list.insertBefore(li, after ? after.nextSibling : list.firstChild);
  } else {
    const before = firstDoneNode();
    list.insertBefore(li, before || null); 
  }
}

