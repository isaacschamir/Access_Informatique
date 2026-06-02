<template>
  <div>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Gestion des administrateurs</h1>
        <p class="text-sm text-slate-600">
          Créer, modifier et supprimer des comptes admin (superadmin only).
        </p>
      </div>
      <button
        @click="loadAdmins"
        class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
      >
        Rafraîchir
      </button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Liste des administrateurs</h2>

        <div v-if="loading" class="text-sm text-slate-500">Chargement...</div>
        <div v-else>
          <table class="min-w-full text-left text-sm text-slate-700">
            <thead>
              <tr class="border-b border-slate-200 text-slate-500">
                <th class="px-3 py-3">Nom</th>
                <th class="px-3 py-3">Email</th>
                <th class="px-3 py-3">Rôle</th>
                <th class="px-3 py-3">Créé le</th>
                <th class="px-3 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="user in admins"
                :key="user.id"
                class="border-b border-slate-100 hover:bg-slate-50"
              >
                <td class="px-3 py-3">{{ user.name }}</td>
                <td class="px-3 py-3">{{ user.email }}</td>
                <td class="px-3 py-3 capitalize">{{ user.role }}</td>
                <td class="px-3 py-3">{{ formatDate(user.created_at) }}</td>
                <td class="px-3 py-3">
                  <button
                    @click="deleteAdmin(user.id)"
                    class="rounded-xl border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                    :disabled="deletingId === user.id"
                  >
                    {{ deletingId === user.id ? 'Suppression…' : 'Supprimer' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="admins.length === 0" class="mt-4 text-sm text-slate-500">
            Aucun administrateur trouvé.
          </div>
        </div>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Créer un administrateur</h2>

        <form @submit.prevent="createAdmin" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Nom</label>
            <input
              v-model="form.name"
              type="text"
              class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Mot de passe</label>
            <input
              v-model="form.password"
              type="password"
              class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Rôle</label>
            <select
              v-model="form.role"
              class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm"
            >
              <option value="editor">editor</option>
              <option value="admin">admin</option>
              <option value="superadmin">superadmin</option>
            </select>
          </div>
          <div class="flex items-center justify-between gap-3">
            <button
              type="submit"
              class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-3 text-sm font-semibold text-white hover:bg-green-700"
            >
              {{ creating ? 'Création…' : 'Créer' }}
            </button>
            <span v-if="message" class="text-sm text-slate-500">{{ message }}</span>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const admins = ref([])
const loading = ref(false)
const creating = ref(false)
const deletingId = ref(null)
const message = ref('')

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'editor',
})

function formatDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('fr-FR')
}

async function loadAdmins() {
  loading.value = true
  message.value = ''
  try {
    const { data } = await api.get('/admin/admins')
    admins.value = data
  } catch (error) {
    message.value = 'Impossible de charger les administrateurs.'
  } finally {
    loading.value = false
  }
}

async function createAdmin() {
  creating.value = true
  message.value = ''
  try {
    await api.post('/admin/admins', {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      role: form.value.role,
    })
    message.value = 'Administrateur créé.'
    form.value.name = ''
    form.value.email = ''
    form.value.password = ''
    form.value.role = 'editor'
    await loadAdmins()
  } catch (error) {
    message.value = error.response?.data?.error || 'Erreur lors de la création.'
  } finally {
    creating.value = false
  }
}

async function deleteAdmin(id) {
  if (!confirm('Supprimer cet administrateur ?')) return
  deletingId.value = id
  message.value = ''
  try {
    await api.delete(`/admin/admins?id=${id}`)
    message.value = 'Administrateur supprimé.'
    await loadAdmins()
  } catch (error) {
    message.value = error.response?.data?.error || 'Impossible de supprimer cet administrateur.'
  } finally {
    deletingId.value = null
  }
}

onMounted(loadAdmins)
</script>
