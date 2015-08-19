#include <stdio.h>

#define MAX_LINE 1024

int main() {
                char line[MAX_LINE];
                FILE *file = fopen("/proc/user_beancounters", "r");
                if (file == NULL) exit(1);

                while (fgets(line, MAX_LINE, file))
                        printf("%s", line);

                fclose(file);
                return 0;
}
