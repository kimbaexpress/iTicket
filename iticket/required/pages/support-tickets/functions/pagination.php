 <!-- Paginación -->
 <div class="flex justify-left mt-6 ml-6">
        <?php if ($total_pages > 1): ?>

            <nav class="inline-flex space-x-1">
                <!-- Botón de página anterior -->
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100">Anterior</a>
                <?php endif; ?>

                <!-- Números de página -->
                <?php
                $range = 2; 
                $start = max(1, $page - $range);
                $end = min($total_pages, $page + $range);
                ?>
                <?php if ($start > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100">1</a>
                    <?php if ($start > 2): ?>
                        <span class="px-3 py-1">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="px-3 py-1 bg-gray-800 border border-gray-300 text-white"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?>
                        <span class="px-3 py-1">...</span>
                    <?php endif; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <!-- Botón de página siguiente -->
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-1 bg-white border border-gray-300 text-gray-500 hover:bg-gray-100">Siguiente</a>
                <?php endif; ?>
            </nav>
    </div>
<?php endif; ?>

</div>