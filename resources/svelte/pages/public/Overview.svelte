<script module>
  import PublicLayout from "../../layouts/PublicLayout.svelte";
  export const layout = PublicLayout;
</script>

<script lang="ts">
  import { t } from "../../lib/i18n";

  interface Props {
    isAuthenticated: boolean;
    username?: string;
    appName: string;
    locale: string;
    currentUrl: string;
  }
  const { isAuthenticated, username, appName, locale, currentUrl }: Props =
    $props();
</script>

<svelte:head><title>{t("overview")} - {appName}</title></svelte:head>

<main
  id="pageContent"
  class="overview elementToPadMenu"
  style="text-align: center;"
>
  <img src="/img/overview/desktop.png" class="screen" alt={t("overview")} />
  <h2>{@html t("welcome")}</h2>
  <p>{@html t("welcome-o")}</p>

  <div class="row">
    <img
      src="/img/overview/file-manager-2.png"
      class="screen"
      alt={t("file2")}
    />
    <img src="/img/overview/file-manager-3.png" class="screen" alt="" />
  </div>
  <h2>{@html t("file2")}</h2>
  <p>{@html t("file-o")}</p>

  <img src="/img/overview/diary-2.png" class="screen" alt={t("diary2")} />
  <h2>{@html t("diary2")}</h2>
  <p>{@html t("diary-o")}</p>

  <img src="/img/overview/timetable.png" class="screen" alt={t("timetable2")} />
  <h2>{@html t("timetable2")}</h2>
  <p>{@html t("timetable-o")}</p>

  <div class="row">
    <img src="/img/overview/share.png" class="screen" alt={t("share2")} />
    <img src="/img/overview/share-2.png" class="screen" alt="" />
  </div>
  <h2>{@html t("share2")}</h2>
  <p>{@html t("share-o")}</p>

  <img src="/img/overview/message.png" class="screen" alt={t("message2")} />
  <h2>{@html t("message2")}</h2>
  <p>{@html t("message-o")}</p>

  <img src="/img/overview/contact.png" class="screen" alt={t("contact2")} />
  <h2>{@html t("contact2")}</h2>
  <p>{@html t("contact-o")}</p>

  <img src="/img/overview/reader.png" class="screen" alt={t("reader")} />
  <h2>{@html t("reader")}</h2>
  <p>{@html t("reader-o")}</p>

  <img src="/img/overview/writer.png" class="screen" alt={t("writer")} />
  <h2>{@html t("writer")}</h2>
  <p>{@html t("writer-o")}</p>

  <div class="row">
    <img src="/img/overview/settings.png" class="screen" alt={t("settings")} />
    <img src="/img/overview/settings-2.png" class="screen" alt="" />
  </div>
  <h2>{@html t("settings")}</h2>
  <p>{@html t("settings-o")}</p>

  <img src="/img/overview/settings-3.png" class="screen" alt={t("privacy")} />
  <h2>{@html t("privacy")}</h2>
  <p>{@html t("privacy-o")}</p>

  <img src="/img/overview/dark.png" class="screen" alt={t("dark")} />
  <h2>{@html t("dark")}</h2>
  <p>{@html t("dark-o")}</p>

  <img
    src="/img/overview/application-launcher.png"
    class="screen"
    alt={t("launcher")}
  />
  <h2>{@html t("launcher")}</h2>
  <p>{@html t("launcher-o")}</p>

  <h3>{@html t("and-now")}</h3>
  <p>
    {@html t("and-now-o")}
    <a href="/features">{@html t("and-now-o2")}</a>
  </p>
</main>

<footer style="background-color: #F6F6F6; padding: 40px">
  <div class="contact container">
    <div class="row">
      <div class="col-md-6">
        <h4>{appName}</h4>
      </div>
      <div class="col-md-6">
        <p style="font-weight: bold">{t("footer-useful-links")}</p>
        <div class="row">
          <div class="col-md-6">
            <p><a href="/privacy">{t("privacy-policy")}</a></p>
          </div>
          <div class="col-md-6"><p><a href="/tos">{t("tos")}</a></p></div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <p><a href="/cookie">{t("cookie-bar")}</a></p>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <p>
              <a href="//www.francescosorge.com/#contact">{t("contact")}</a>
            </p>
          </div>
        </div>
        <p>
          <a href={"/language?redirect=" + encodeURIComponent(currentUrl)}>
            <img
              src={"/img/language/" + locale + ".png"}
              style="width: 26px; margin-right: 5px"
              alt=""
            />
            {t("change-language")}
          </a>
        </p>
      </div>
    </div>
  </div>
</footer>

<style lang="scss">
  main {
    padding: 2rem;

    .row,
    img,
    h2,
    h3,
    :global(p) {
      margin: 0;
      padding: 0 !important;
    }

    p {
      margin-bottom: 5rem;
    }

    img {
      width: 100%;
      max-width: 1400px;
    }

    .row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
  }
</style>
