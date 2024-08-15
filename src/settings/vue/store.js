import { defineStore } from "pinia";
import { ref, watch, computed } from "vue";

export const useWpjcStore = defineStore("wpjcstore", () => {
  const initial = ref("Settings");

  /* watch(activetab, (newactivetab, prevactivetab) => {
      
    }) */

  /* function increment() {
      count.value++
    } */

  //return { zoomlevel, doubleCount, increment }
  return {
    initial,
  };
});
