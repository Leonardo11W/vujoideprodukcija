import { createApp } from "vue";
import { createI18n } from "vue-i18n";
import { createPinia } from 'pinia';
import VueformMultiselect from "@vueform/multiselect";
import BootstrapVueNext from 'bootstrap-vue-next'
import "@vueform/multiselect/themes/default.css";
import 'bootstrap-vue-next/dist/bootstrap-vue-next.css'
export const InitApp = (component) => {
  const pinia = createPinia();

  let app
  if(component != undefined) {
    app = createApp(component);
  } else {
    app = createApp();
  }
    /**
     *
     * !Usage : $t('{key_name}')
     */
    const rawLang = (typeof document !== "undefined" && document.documentElement && document.documentElement.lang)
        ? document.documentElement.lang
        : "hr";
    const appLocale = rawLang.length >= 2 ? rawLang.toLowerCase().split("-")[0] : "hr";
    const i18n = createI18n({
        legacy: false,
        locale: appLocale,
        fallbackLocale: "en",
        globalInjection: true,
        messages: { [appLocale]: window.localMessagesUpdate || {} },
    });

    window.i18n = i18n

    app.use(pinia);
    app.use(i18n);
    app.use(BootstrapVueNext)
    app.config.globalProperties.msg = "hello";
    app.component("Multiselect", VueformMultiselect);
    return app;
};
