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
  const {
    isAuthenticated: _isAuthenticated,
    username: _username,
    appName,
    locale,
    currentUrl,
  }: Props = $props();
</script>

<svelte:head><title>{t("features")} - {appName}</title></svelte:head>

<div id="pageContent" class="features elementToPadMenu">
  <main class="container">
    <div id="notebook">
      <h3>{@html t("notebook")}</h3>
      <p>{@html t("notebook-d")}</p>
    </div>
    <div id="file">
      <h3>{@html t("file")}</h3>
      <p>{@html t("file-d")}</p>
    </div>
    <div id="share">
      <h3>{@html t("share-2")}</h3>
      <p>{@html t("share-d")}</p>
    </div>
    <div id="project">
      <h3>{@html t("project-2")}</h3>
      <p>
        {@html t("project-d")} <a href="/my/app/project/"
          >{@html t("project-d2")}</a
        >
        {@html t("project-d3")}
      </p>
    </div>
    <div id="diary">
      <h3>{@html t("diary")}</h3>
      <p>{@html t("diary-d")}</p>
    </div>
    <div id="timetable">
      <h3>{@html t("timetable")}</h3>
      <p>{@html t("timetable-d")}</p>
    </div>
    <div id="message">
      <h3>{@html t("message")}</h3>
      <p>{@html t("message-d")}</p>
    </div>
    <div id="contact">
      <h3>{@html t("contact-2")}</h3>
      <p>{@html t("contact-d")}</p>
    </div>
    <div id="security">
      <h3>{@html t("security")}</h3>
      <p>{@html t("security-d")}</p>
    </div>
    <div id="customize">
      <h3>{@html t("customize")}</h3>
      <p>{@html t("customize-d")}</p>
    </div>
    <div id="cross">
      <h3>{@html t("cross")}</h3>
      <p>{@html t("cross-d")}</p>
    </div>
    <div id="easy">
      <h3>{@html t("easy")}</h3>
      <p>{@html t("easy-d")}</p>
    </div>
    <div id="free">
      <h3>{@html t("free")}</h3>
      <p>{@html t("free-d")}</p>
    </div>
  </main>

  <!-- footer -->
  <div style="background-color: #F6F6F6; padding: 40px">
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
  </div>
</div>

<style lang="scss">
  main div {
    margin-bottom: 2rem;

    h3,
    :global(p) {
      margin: 0;
      padding: 0;
    }
  }
</style>
