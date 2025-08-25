<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Your Contacts</h3>
                    <button id="addContactBtn" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">Add Contact</button>
                </div>

                <div id="contactsList" class="space-y-4">
                    <!-- Contacts will be loaded here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <div id="addContactModal" class="fixed inset-0 z-50 hidden" tabindex="-1" aria-hidden="true">
      <!-- overlay -->
      <div class="absolute inset-0 bg-black/40" onclick="hideAddModal()"></div>

      <!-- panel -->
      <div class="absolute z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-lg w-full max-w-sm p-5">
        <div>
          <h4 class="text-base font-semibold">Add Contact</h4>
        </div>
        <form id="addContactForm" class="mt-3 space-y-3">
          <div>
            <label for="name" class="block text-xs font-medium mb-1">Name</label>
            <input type="text" class="w-full p-2 border rounded text-sm" name="name" id="name" required>
          </div>
          <div>
            <label for="phone_number" class="block text-xs font-medium mb-1">Phone Number</label>
            <input type="text" class="w-full p-2 border rounded text-sm" name="phone_number" id="phone_number" required>
          </div>
          <div id="errorMsg" class="hidden text-xs text-red-600">User with this phone number not found.</div>
          <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" class="text-sm px-3 py-1 border rounded" id="cancelBtn" onclick="hideAddModal()">Cancel</button>
            <button type="submit" class="text-sm px-3 py-1 border rounded bg-indigo-600 text-white">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Contact (Tailwind-style modal) -->
<div id="editContactModal" class="fixed inset-0 z-50 hidden">
  <!-- overlay -->
  <div class="absolute inset-0 bg-black/40" onclick="hideEditModal()"></div>

  <!-- panel -->
  <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-lg w-full max-w-sm p-5">
    <div class="flex items-start justify-between">
      <h4 class="text-base font-semibold">Edit Contact</h4>
    </div>

    <form id="editContactForm" class="mt-3 space-y-3">
      <input type="hidden" name="id" id="editContactId">

      <div>
        <label for="editName" class="block text-xs font-medium mb-1">Name</label>
        <input type="text"
               class="w-full p-2 border rounded text-sm"
               name="name" id="editName" required>
      </div>

      <div id="editErrorMsg" class="hidden text-xs text-red-600"></div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button"
                class="text-sm px-3 py-1 border rounded"
                onclick="document.getElementById('editContactModal').classList.add('hidden')"
                id="editCancelBtn">
          Cancel
        </button>
        <button type="submit"
                class="text-sm px-3 py-1 border rounded bg-indigo-600 text-white">
          Save
        </button>
      </div>
    </form>
  </div>
</div>
</x-app-layout>
<script>
    const addModalEl = document.getElementById('addContactModal');
    const editModalEl = document.getElementById('editContactModal');

    function showAddModal(){
        const form = document.getElementById('addContactForm');
        if (form) form.reset();
        const err = document.getElementById('errorMsg');
        if (err) err.classList.add('hidden');
        addModalEl.classList.remove('hidden');
    }
    function hideAddModal(){
        addModalEl.classList.add('hidden');
        const form = document.getElementById('addContactForm');
        if (form) form.reset();
        const err = document.getElementById('errorMsg');
        if (err) err.classList.add('hidden');
    }
    function hideEditModal(){
        editModalEl.classList.add('hidden');
        const form = document.getElementById('editContactForm');
        if (form) form.reset();
        const err = document.getElementById('editErrorMsg');
        if (err) err.classList.add('hidden');
    }

    document.getElementById('addContactBtn').addEventListener('click', showAddModal);

    document.getElementById('addContactForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const errorEl = document.getElementById('errorMsg');
        errorEl.classList.add('hidden');

        try {
            const response = await fetch("{{ route('contacts.store') }}", {
                method: "POST",
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                },
                body: formData,
            });

            if (response.status === 422) {
                const err = await response.json();
                // Prefer Laravel validation format: errors.{field}[0]
                const msg = (err && err.errors && (err.errors.phone_number?.[0] || err.errors.phone?.[0]))
                    || err.message || 'Validation failed';
                errorEl.textContent = msg;
                errorEl.classList.remove('hidden');
                return;
            }

            if (response.status === 409) {
                let msg = 'Contact already exists';
                try {
                    const j = await response.json();
                    msg = j.message || msg;
                } catch(_) {}
                errorEl.textContent = msg;
                errorEl.classList.remove('hidden');
                return;
            }

            if (!response.ok) {
                let msg = 'Failed to create contact';
                try {
                    const j = await response.json();
                    msg = (j && (j.message || j.error)) || msg;
                } catch(_) {
                    const txt = await response.text();
                    if (txt) msg = txt;
                }
                errorEl.textContent = msg;
                errorEl.classList.remove('hidden');
                return;
            }

            // Success
            await loadContacts();
            addModalEl.classList.add('hidden');
            form.reset();
        } catch (ex) {
            console.error(ex);
            errorEl.textContent = 'Network error';
            errorEl.classList.remove('hidden');
        }
    });

    async function loadContacts(){
        const res = await fetch("{{ route('contacts.contacts') }}");
        const contacts = await res.json();
        const container = document.getElementById('contactsList');
        container.innerHTML = "";
        contacts.forEach(c => {
            const firstLetter = c.name.charAt(0).toUpperCase();
            const contactItem = document.createElement('div');
            contactItem.className = 'flex items-center justify-between bg-white border border-gray-200 rounded-md p-4';

            contactItem.innerHTML = `
                <div class="flex items-center space-x-4">
                    <div class="rounded-full bg-blue-500 w-10 h-10 flex items-center justify-center text-white text-lg font-bold">${firstLetter}</div>
                    <div>
                        <div class="font-medium text-gray-900">${c.name}</div>
                        <div class="text-sm text-gray-500">${c.phone_number}</div>
                    </div>
                </div>
                <div>
                    <button onclick="editContact(${c.id})" class="text-blue-600 hover:text-blue-800 mr-3" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"/>
                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 110 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <button onclick="deleteContact(${c.id})" class="text-red-600 hover:text-red-800" title="Delete">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2h12a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM5 6a1 1 0 011 1v9a2 2 0 002 2h4a2 2 0 002-2V7a1 1 0 112 0v9a4 4 0 01-4 4H8a4 4 0 01-4-4V7a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            `;
            container.appendChild(contactItem);
        });
    }

    async function deleteContact(id){
        if(!confirm("Are you sure?")) return;
        await fetch(`contacts/${id}`, {
            method: "DELETE",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
        });
        loadContacts();
    }

    async function editContact(id){
        try {
            const response = await fetch(`contacts/${id}`);
            if (!response.ok) {
                alert('Failed to fetch contact details');
                return;
            }
            const contact = await response.json();
            document.getElementById('editContactId').value = contact.id;
            document.getElementById('editName').value = contact.name;
            const errorEl = document.getElementById('editErrorMsg');
            errorEl.classList.add('hidden');
            editModalEl.classList.remove('hidden');
        } catch (ex) {
            console.error(ex);
            alert('Network error');
        }
    }

    document.getElementById('editContactForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);
        formData.append('_method', 'PUT');
        const id = formData.get('id');
        const errorEl = document.getElementById('editErrorMsg');
        errorEl.classList.add('hidden');

        try {
            const response = await fetch(`contacts/${id}`, {
                method: "POST",
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                },
                body: formData,
            });

            if (response.status === 422) {
                const err = await response.json();
                const msg = (err && err.errors && (err.errors.name?.[0]))
                    || err.message || 'Validation failed';
                errorEl.textContent = msg;
                errorEl.classList.remove('hidden');
                return;
            }

            if (!response.ok) {
                const txt = await response.text();
                console.error('Update contact failed:', response.status, txt);
                errorEl.textContent = 'Failed to update contact';
                errorEl.classList.remove('hidden');
                return;
            }

            await loadContacts();
            editModalEl.classList.add('hidden');
            form.reset();
        } catch (ex) {
            console.error(ex);
            errorEl.textContent = 'Network error';
            errorEl.classList.remove('hidden');
        }
    });

    document.addEventListener('DOMContentLoaded', loadContacts);
</script>