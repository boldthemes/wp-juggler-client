<script setup>

import { useWpjcStore } from './store.js'
import { onMounted, computed, ref } from 'vue'
import { useQueryClient, useQuery, useMutation } from '@tanstack/vue-query'

const queryClient = useQueryClient()

const store = useWpjcStore()

const nonce = ref(wpjc_settings_object.nonce)

const wpjc_api_key = ref('')
const wpjc_server_url = ref('')
const save_loading = ref(false)

const snackbar = ref(false)
const snackbar_color = ref('success')
const snackbar_text = ref(snack_succ_text)
const snack_succ_text = 'WP Juggler Settings Saved & Site Activated'
const snack_error_text = 'WP Juggler Site Activation Failed'


const { isLoading, isError, isFetching, data, error, refetch } = useQuery({
  queryKey: ['wpjc-settings'],
  queryFn: getSettings,
  refetchOnWindowFocus: false
})

const mutation = useMutation({
  mutationFn: saveSettings,
  onSuccess: async () => {
    // Invalidate and refetch
    queryClient.invalidateQueries({ queryKey: ['wpjc-settings'] })
    save_loading.value = false

    snackbar_color.value = 'success'
    snackbar_text.value = snack_succ_text
    snackbar.value = true
  },
  onError: (error, variables, context) => {
    queryClient.invalidateQueries({ queryKey: ['wpjc-settings'] })
    save_loading.value = false

    snackbar_color.value = 'error'
    snackbar_text.value = 'Error Code: ' + error.responseJSON.data[0].code + ' - ' + error.responseJSON.data[0].message
    snackbar.value = true
  },
})

async function doAjax(args) {
  let result;
  try {
    result = await jQuery.ajax({
      url: wpjc_settings_object.ajaxurl,
      type: 'POST',
      data: args
    });
    return result;
  } catch (error) {
    throw (error)
  }
}

async function getSettings() {

  let ret = {}
  const response = await doAjax(
    {
      action: "wpjc_get_settings",  // the action to fire in the server
    }
  )
  ret = response.data

  wpjc_api_key.value = response.data.wpjc_api_key
  wpjc_server_url.value = response.data.wpjc_server_url

  return ret
}

function clickSaveSettings() {
  save_loading.value = true
  mutation.mutate({
    wpjc_api_key: wpjc_api_key.value,
    wpjc_server_url: wpjc_server_url.value
  })
}

async function saveSettings(obj) {

  obj.action = "wpjc_save_settings"
  obj.nonce = nonce.value

  const response = await doAjax(obj)
}

</script>

<template>

  <h1>WP Juggler Client Settings</h1>

  <v-card class="pa-4 mr-4" v-if="data">

    <table class="form-table" role="presentation">

      <tbody v-if="data">
        <tr>
          <th scope="row"><label for="api key">WP Juggler API Key</label></th>
          <td>
            <input type="text" size="50" placeholder="" v-model="wpjc_api_key">
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="server url">WP Server Url</label></th>
          <td>
            <input type="text" size="50" placeholder="" v-model="wpjc_server_url">
          </td>
        </tr>

      </tbody>
    </table>
    <p></p>
    <v-divider class="border-opacity-100"></v-divider>

    <p></p>

    <v-btn variant="flat" class="text-none text-caption" color="#2271b1" @click="clickSaveSettings"
      :loading="save_loading">
      Save Settings
    </v-btn>

    <v-snackbar v-model="snackbar" :timeout="3000" :color="snackbar_color">
      {{ snackbar_text }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false">
          X
        </v-btn>
      </template>
    </v-snackbar>

  </v-card>
  <v-card class="pa-4 mr-4" v-else>
    <v-skeleton-loader type="table-tbody" > </v-skeleton-loader>
  </v-card>
</template>

<style></style>