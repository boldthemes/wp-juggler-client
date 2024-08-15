import { defineStore } from "pinia";
import { ref, watch, computed } from "vue";

export const useWpjcStore = defineStore("wpjcstore", () => {
  const initial = ref("Dashboard");

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
