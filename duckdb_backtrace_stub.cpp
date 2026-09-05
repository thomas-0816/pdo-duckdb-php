/*
 * crtbeginS.o's frame_dummy calls __register_frame_info() to register the
 * .eh_frame of this large library at dlopen time. Alpine's libgcc (musl)
 * segfaults while walking that unwind table. Registration is unnecessary
 * though: exception unwinding finds .eh_frame via the PT_GNU_EH_FRAME
 * header using dl_iterate_phdr. Provide hidden no-ops so crtbegin's calls
 * bind to these locally instead of libgcc's implementation. glibc registers
 * the .eh_frame correctly, so this only compiles on non-glibc Linux.
 */
#if defined(__linux__) && !defined(__GLIBC__)

extern "C" __attribute__((visibility("hidden"))) void __register_frame_info(void const *, void *) {
}

extern "C" __attribute__((visibility("hidden"))) void __register_frame_info_bases(void const *, void *,
                                                                                 void *, void *) {
}

extern "C" __attribute__((visibility("hidden"))) void __deregister_frame_info(void const *, void *) {
}

extern "C" __attribute__((visibility("hidden"))) void __deregister_frame_info_bases(void const *, void *) {
}

#endif