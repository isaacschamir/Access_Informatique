<template>
  <div>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Gestion des administrateurs</h1>
        <p class="text-sm text-slate-600">
          {{
            isSuperAdmin
              ? 'Créer, modifier, supprimer et gérer les rôles des admins.'
              : 'Voir tous les admins et créer uniquement des éditeurs.'
          }}
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
                  <template v-if="isSuperAdmin">
                    <button
                      @click="startEdit(user)"
                      class="mr-2 rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    >
                      Modifier
                    </button>
                    <button
                      @click="deleteAdmin(user.id)"
                      class="rounded-xl border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                      :disabled="deletingId === user.id"
                    >
                      {{ deletingId === user.id ? 'Suppression…' : 'Supprimer' }}
                    </button>
                  </template>
                  <template v-else>
                    <span class="text-xs text-slate-400">Actions réservées au superadmin</span>
                  </template>
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
            <div
              v-if="isAdmin"
              class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
            >
              editor
            </div>
            <select
              v-else
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
              {{
                creating
                  ? editingId
                    ? 'Mise à jour…'
                    : 'Enregistrement…'
                  : editingId
                    ? 'Mettre à jour'
                    : 'Créer'
              }}
            </button>
            <button
              v-if="editingId"
              type="button"
              @click="resetForm"
              class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
              Annuler
            </button>
            <span v-if="message" class="text-sm text-slate-500">{{ message }}</span>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import api from '@/services/api'
import { useAdminStore } from '@/stores/admin'

const adminStore = useAdminStore()
const isSuperAdmin = computed(() => adminStore.admin?.role === 'superadmin')
const isAdmin = computed(() => adminStore.admin?.role === 'admin')

const admins = ref([])
const loading = ref(false)
const creating = ref(false)
const deletingId = ref(null)
const editingId = ref(null)
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

function resetForm() {
  form.value.name = ''
  form.value.email = ''
  form.value.password = ''
  form.value.role = 'editor'
  editingId.value = null
}

function startEdit(user) {
  editingId.value = user.id
  form.value.name = user.name
  form.value.email = user.email
  form.value.password = ''
  form.value.role = user.role
  message.value = ''
}

async function createAdmin() {
  creating.value = true
  message.value = ''
  try {
    const payload = {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      role: form.value.role,
    }

    if (isAdmin.value) {
      payload.role = 'editor'
    }

    if (editingId.value) {
      await api.put(`/admin/admins?id=${editingId.value}`, payload)
      message.value = 'Administrateur mis à jour.'
    } else {
      await api.post('/admin/admins', payload)
      message.value = 'Administrateur créé.'
    }

    resetForm()
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
