const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

async function handleCreateExerciseFormSubmit(e) {
  e.preventDefault();

  const promptEl = document.getElementById('promptInput');
  const answerEl = document.getElementById('answerInput');

  try {
    const res = await fetch('/exercises', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ prompt: promptEl.value, answer: answerEl.value })
    });

    if (!res.ok) throw new Error(await res.text());

    const exercise = await res.json();

    const li = document.createElement('li');
    li.className = "flex items-center px-4 py-3";
    li.dataset.id = exercise.id;
    li.innerHTML = `
      <span class="ml-3 flex-1">${exercise.prompt}</span>
    `;
    document.getElementById('exercisesList').prepend(li);
    promptEl.value = '';
    answerEl.value = '';

  } catch (err) {
    console.error('Error:', err);
  }
}
