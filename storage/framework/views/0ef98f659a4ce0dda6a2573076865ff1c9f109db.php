
<?php $__env->startSection('content'); ?>
    <div class="wrapper">
        <section>
            <div class="row justify-content-center align-items-center g-0 w-100 h-100vh">
                <div class="col-xl-4 col-lg-6 col-sm-8 col-auto px-5">
                    <div class="card box-shadow overflow-hidden border-0">
                        <div class="bg-primary-light">
                            <div class="row">
                                <div class="col-7 d-flex align-items-center">
                                    <div class="text-primary p-4">
                                        <h4><?php echo e(trans('labels.forgotpassword')); ?></h4>
                                    </div>
                                </div>
                                <div class="col-5 align-self-end">
                                    <img src="<?php echo e(helper::image_path('login-img.png')); ?>"
                                        class="img-fluid" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <form class="my-3" method="POST" action="<?php echo e(URL::to('/sendpassword')); ?>">
                                <?php echo csrf_field(); ?>
                                <div class="form-group">
                                    <label for="email" class="form-label"><?php echo e(trans('labels.email')); ?> <span class="text-danger"> * </span></label>
                                    <input type="email" class="form-control" name="email" value="" id="email"
                                        placeholder="<?php echo e(trans('labels.email')); ?>" required>
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="text-danger"><?php echo e($message); ?></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <button class="btn btn-primary w-100 my-3"
                                    type="submit"><?php echo e(trans('labels.submit')); ?></button>
                                <p class="fs-7 text-center"><?php echo e(trans('labels.remember_password')); ?>

                                    <a href="<?php echo e(URL::to('/admin')); ?>"
                                        class="text-primary fw-semibold"><?php echo e(trans('labels.login')); ?></a>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.auth_default', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\market.mrcode.ir-ai-writer-saas-v2.0\aiwriter\resources\views/admin/auth/forgotpassword.blade.php ENDPATH**/ ?>