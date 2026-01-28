<?php
/** @var array $stylist */
/** @var array $reps */
/** @var array $celebs */
/** @var array $available_celebs */
/** @var int $stylist_id */
/** @var string $back_url */
?>

<style>
    .moda-stylist-actions {
        display: flex;
        align-items: center;
        justify-content: flex-start;
    }
</style>

<p><a href="<?php echo esc_url($back_url); ?>">&larr; Back to list</a></p>

<h2>Stylist</h2>
<div class="moda-stylist-actions" style="margin-bottom: 12px;">
    <a href="#" class="edit-stylists edit">Edit stylist</a>
    <button type="button" class="button button-primary save-stylist" style="display:none; margin-left: 10px;">Save Changes</button>
</div>

<input type="hidden" name="stylist_id" value="<?php echo esc_attr($stylist_id); ?>" />

<table class="widefat striped stylist-data" style="max-width: 900px;">
    <tbody>
        <tr>
            <th style="width:180px;">Full name</th>
            <td>
                <input type="text" name="stylist_full_name" value="<?php echo esc_html($stylist['full_name']); ?>" readonly class="value-input" style="display:none; width: 100%;" />
                <span class="value-item"><?php echo esc_html($stylist['full_name']); ?></span>
                
            </td>
        </tr>
        <tr>
            <th>Email</th>
            <td>
                <input type="text" name="stylist_email" value="<?php echo esc_html($stylist['email']); ?>" readonly class="value-input" style="display:none; width: 100%;" />
                <span class="value-item"><?php echo esc_html($stylist['email']); ?></span>
            </td>
        </tr>
        <tr>
            <th>Phone</th>
            <td>
                <input type="text" name="stylist_phone" value="<?php echo esc_html($stylist['phone']); ?>" readonly class="value-input" style="display:none; width: 100%;" />
                <span class="value-item"><?php echo esc_html($stylist['phone']); ?></span>
            </td>
        </tr>
        <tr>
            <th>Instagram</th>
            <td>
                <input type="text" name="stylist_instagram" value="<?php echo esc_html($stylist['instagram']); ?>" readonly class="value-input" style="display:none; width: 100%;" />
                <span class="value-item"><?php echo esc_html($stylist['instagram']); ?></span>
            </td>
        </tr>
        <tr>
            <th>Website</th>
            <td>
                <input type="text" name="stylist_website" value="<?php echo esc_html($stylist['website']); ?>" readonly class="value-input" style="display:none; width: 100%;" />
                <span class="value-item"><?php echo esc_html($stylist['website']); ?></span>
            </td>
        </tr>
    </tbody>
</table>

<h2 style="margin-top:24px;">Representatives</h2>
<?php if (!$reps): ?>
    <p>No reps found.</p>
<?php else: ?>
    <table class="widefat striped" style="max-width: 900px;">
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Territory</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reps as $rep): ?>
                <tr>
                    <td>
                        <?php echo esc_html($rep['rep_name']); ?>
                    </td>
                    <td>
                        <?php echo esc_html($rep['company']); ?>
                    </td>
                    <td>
                        <?php echo esc_html($rep['rep_email']); ?>
                    </td>
                    <td>
                        <?php echo esc_html($rep['rep_phone']); ?>
                    </td>
                    <td>
                        <?php echo esc_html($rep['territory']); ?>
                    </td>
                    <td>
                        <form method="post" style="display:flex; justify-content:flex-end; align-items:center;">
                            <?php wp_nonce_field('moda_admin_action'); ?>
                            <input type="hidden" name="moda_action" value="remove_rep" />
                            <input type="hidden" name="stylist_id" value="<?php echo esc_attr($stylist_id); ?>" />
                            <input type="hidden" name="rep_id" value="<?php echo esc_attr($rep['id']); ?>" />
                            <a href="#" class="edit-repo" style="margin-right: 10px;">edit</a>
                            <?php submit_button('Remove', 'delete', 'submit', false); ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h3 style="margin-top:16px;">Add representative</h3>
<form method="post" style="max-width: 900px;">
    <?php wp_nonce_field('moda_admin_action'); ?>
    <input type="hidden" name="moda_action" value="add_rep" />
    <input type="hidden" name="stylist_id" value="<?php echo esc_attr($stylist_id); ?>" />
    <table class="form-table">
        <tbody>
            <tr>
                <th><label for="rep_name">Name</label></th>
                <td><input name="rep_name" id="rep_name" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="company">Company</label></th>
                <td><input name="company" id="company" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="rep_email">Email</label></th>
                <td><input name="rep_email" id="rep_email" class="regular-text" type="email"></td>
            </tr>
            <tr>
                <th><label for="rep_phone">Phone</label></th>
                <td><input name="rep_phone" id="rep_phone" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="territory">Territory</label></th>
                <td><input name="territory" id="territory" class="regular-text"></td>
            </tr>
        </tbody>
    </table>
    <?php submit_button('Add rep'); ?>
</form>

<h2 style="margin-top:24px;">Celebrities</h2>
<?php if (!$celebs): ?>
    <p>No celebrities attached.</p>
<?php else: ?>
    <table class="widefat striped" style="max-width: 900px;">
        <thead><tr><th>Name</th><th>Industry</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($celebs as $celeb): ?>
                <tr>
                    <td><?php echo esc_html($celeb['full_name']); ?></td>
                    <td><?php echo esc_html($celeb['industry']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('moda_admin_action'); ?>
                            <input type="hidden" name="moda_action" value="detach_celebrity" />
                            <input type="hidden" name="stylist_id" value="<?php echo esc_attr($stylist_id); ?>" />
                            <input type="hidden" name="celebrity_id" value="<?php echo esc_attr($celeb['id']); ?>" />
                            <?php submit_button('Detach', 'secondary', 'submit', false); ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h3 style="margin-top:16px;">Attach celebrity</h3>
<form method="post" style="max-width: 900px;">
    <?php wp_nonce_field('moda_admin_action'); ?>
    <input type="text" name="search_celebrity" value="" />
    <input type="hidden" name="moda_action" value="attach_celebrity" />
    <input type="hidden" name="stylist_id" value="<?php echo esc_attr($stylist_id); ?>" />
    <select name="celebrity_id" required>
        <option value="">Select celebrity</option>
    </select>
    <?php submit_button('Attach', 'primary', 'submit', false); ?>
</form>
