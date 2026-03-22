<?php

declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/payment_helpers.php';

$client = require_auth('client');
$targetType = (string) ($_GET['type'] ?? '');
$targetId = (int) ($_GET['id'] ?? 0);
$target = payment_target_for_client((int) $client['id'], $targetType, $targetId);

if (!$target) {
    set_flash('error', 'No se encontro el pago solicitado.');
    redirect('client.php');
}

if (!payment_target_is_payable($target)) {
    set_flash('error', 'Este pago no esta disponible.');
    redirect(payment_target_redirect($target));
}

$culqiEnabled = culqi_is_enabled();
$checkoutConfig = culqi_checkout_config();
$amountInCents = (int) round($target['amount'] * 100);

render_header('Checkout de pago');
?>
<section class="card payment-shell">
  <p class="eyebrow">Checkout</p>
  <h1>Pagar a <?= e($target['provider_name']) ?></h1>
  <p class="muted">Metodo habilitado: tarjetas de credito/debito y Yape usando Culqi.</p>

  <div class="grid two payment-grid">
    <section class="item payment-summary">
      <h3><?= e($target['summary']) ?></h3>
      <p class="muted"><?= e($target['description'] !== '' ? $target['description'] : 'Sin descripcion adicional.') ?></p>
      <div class="meta">
        <span class="chip"><?= e(format_amount($target['amount'])) ?></span>
        <span class="chip"><?= e($target['provider_name']) ?></span>
      </div>
      <div class="payment-methods">
        <div class="payment-method-card">
          <strong>Tarjeta</strong>
          <span>Credito y debito</span>
        </div>
        <div class="payment-method-card yape">
          <strong>Yape</strong>
          <span>Pago con codigo de aprobacion</span>
        </div>
      </div>
    </section>

    <section class="item payment-action-panel">
      <?php if ($culqiEnabled): ?>
        <h3>Completar pago</h3>
        <p class="muted">El checkout oficial se abrira para elegir tarjeta o Yape.</p>
        <div class="toolbar">
          <button type="button" id="payButton">Pagar <?= e(format_amount($target['amount'])) ?></button>
          <a class="button secondary" href="<?= e(route_url(payment_target_redirect($target))) ?>">Cancelar</a>
        </div>
        <p class="muted payment-note" id="paymentStatus">Aun no se inicio el cobro.</p>
      <?php else: ?>
        <h3>Configura Culqi</h3>
        <p class="muted">Falta activar las llaves en <code>app/config.php</code>: <code>culqi_enabled</code>, <code>culqi_public_key</code> y <code>culqi_private_key</code>.</p>
        <div class="toolbar">
          <a class="button secondary" href="<?= e(route_url(payment_target_redirect($target))) ?>">Volver</a>
        </div>
      <?php endif; ?>
    </section>
  </div>
</section>

<?php if ($culqiEnabled): ?>
  <script src="https://js.culqi.com/checkout-js"></script>
  <script>
    (function () {
      var payButton = document.getElementById('payButton');
      var statusNode = document.getElementById('paymentStatus');
      var culqiInstance;

      if (!payButton || typeof CulqiCheckout === 'undefined') {
        return;
      }

      function setStatus(message, isError) {
        statusNode.textContent = message;
        statusNode.classList.toggle('payment-error', !!isError);
      }

      var settings = {
        title: <?= json_encode(app_name(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        currency: 'PEN',
        amount: <?= $amountInCents ?>,
        <?php if ($checkoutConfig['rsa_id'] !== '' && $checkoutConfig['rsa_public_key'] !== ''): ?>
        xculqirsaid: <?= json_encode($checkoutConfig['rsa_id'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        rsapublickey: <?= json_encode($checkoutConfig['rsa_public_key'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        <?php endif; ?>
      };

      var config = {
        settings: settings,
        client: {
          email: <?= json_encode((string) $client['email'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        },
        options: {
          lang: 'es',
          installments: true,
          modal: true,
          paymentMethods: {
            tarjeta: true,
            yape: true
          },
          paymentMethodsSort: ['tarjeta', 'yape']
        },
        appearance: {
          theme: 'default',
          menuType: 'sidebar',
          buttonCardPayText: 'Pagar <?= e(format_amount($target['amount'])) ?>',
          defaultStyle: {
            bannerColor: '#0f172a',
            buttonBackground: '#2563eb',
            menuColor: '#0f766e',
            linksColor: '#1d4ed8',
            buttonTextColor: '#ffffff',
            priceColor: '#0f172a'
          }
        }
      };

      culqiInstance = new CulqiCheckout(
        <?= json_encode($checkoutConfig['public_key'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        config
      );

      function processPayment(sourceId, paymentMethod) {
        payButton.disabled = true;
        setStatus('Procesando pago...', false);

        fetch(<?= json_encode(route_url('payment-process.php'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            target_type: <?= json_encode($target['type'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            target_id: <?= $target['id'] ?>,
            source_id: sourceId,
            payment_method: paymentMethod
          })
        })
          .then(function (response) {
            return response.json();
          })
          .then(function (payload) {
            if (!payload.ok) {
              throw new Error(payload.message || 'No se pudo completar el pago.');
            }

            setStatus(payload.message, false);
            window.location.href = payload.redirect;
          })
          .catch(function (error) {
            payButton.disabled = false;
            setStatus(error.message || 'No se pudo completar el pago.', true);
          });
      }

      payButton.addEventListener('click', function () {
        setStatus('Abriendo checkout...', false);
        culqiInstance.open();
      });

      culqiInstance.culqi = function () {
        if (culqiInstance.token) {
          var sourceId = culqiInstance.token.id;
          var paymentMethod = sourceId.indexOf('ype_') === 0 ? 'yape' : (culqiInstance.token.type || 'tarjeta');
          culqiInstance.close();
          processPayment(sourceId, paymentMethod);
          return;
        }

        if (culqiInstance.error) {
          setStatus(culqiInstance.error.user_message || culqiInstance.error.merchant_message || 'El checkout devolvio un error.', true);
          return;
        }

        setStatus('El checkout no devolvio un token valido.', true);
      };
    })();
  </script>
<?php endif; ?>
<?php render_footer(); ?>
